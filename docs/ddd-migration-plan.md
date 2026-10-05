# Reservation: migration plan to a DDD architecture

Status: **plan only — nothing has been changed yet.**

## 1. Where we are today

A classic Symfony layered setup, organised by technical type:

| File | Role today | Problem from a DDD point of view |
|---|---|---|
| `src/Entity/Reservation.php` | Doctrine entity, getters/setters, validation attributes | *Anemic*: no behaviour, anyone can call `setStatus('banana')`. Depends on Doctrine and Validator. `GreaterThan('now')` on the entity breaks for past reservations. |
| `src/Service/ReservationService.php` | All business rules (status checks, create/update/confirm/cancel) + persistence (`flush()`) | Rules live outside the object they protect. Throws `ConflictHttpException`, an HTTP concept, from business code. |
| `src/Repository/ReservationRepository.php` | Doctrine repository | The service depends on Doctrine directly, not on an abstraction. |
| `src/Dto/ReservationInput.php` | Request body for POST/PUT | Fine — stays as an HTTP-layer DTO. |
| `src/Controller/ReservationController.php` | Thin JSON controller | Returns the entity itself as JSON, so the database shape *is* the API contract. |

Tests: 6 HTTP tests (`tests/Controller`) + 2 service tests (`tests/Service`).

## 2. Target architecture

### Layers and the dependency rule

```
UI (Controllers, request DTOs)          ──┐
Infrastructure (Doctrine, Symfony glue) ──┼──►  Application (use cases)  ──►  Domain
                                          │
          dependencies only point inward ─┘     Domain depends on NOTHING (no Symfony, no Doctrine)
```

| Layer | Contains | May depend on |
|---|---|---|
| **Domain** | Aggregate `Reservation`, value objects, `ReservationStatus` enum, domain exceptions, repository **interface**, domain events | PHP only (plus `Psr\Clock\ClockInterface` if needed) |
| **Application** | Use cases: commands/queries + their handlers, read models (views) | Domain |
| **Infrastructure** | Doctrine repository implementation, Doctrine mapping, custom DBAL types | Domain, Application, Doctrine, Symfony |
| **UI** (a.k.a. Presentation) | Controllers, request DTOs, exception → HTTP mapping | Application, Symfony |

### Folder structure (one bounded context: `Booking`)

```
src/
└── Booking/
    ├── Domain/
    │   ├── Reservation.php                     # aggregate root, with behaviour
    │   ├── ReservationId.php                   # value object
    │   ├── ReservationStatus.php               # backed enum: pending|confirmed|cancelled
    │   ├── CustomerName.php                    # value object (non-empty, ≤255)
    │   ├── Email.php                           # value object (valid format)
    │   ├── ReservationRepository.php           # INTERFACE
    │   ├── Event/ReservationConfirmed.php      # domain events (optional, phase 7)
    │   └── Exception/
    │       ├── ReservationNotFound.php
    │       ├── InvalidStatusTransition.php
    │       └── ReservationDateInThePast.php
    ├── Application/
    │   ├── Command/
    │   │   ├── CreateReservation.php           + CreateReservationHandler.php
    │   │   ├── UpdateReservation.php           + UpdateReservationHandler.php
    │   │   ├── ConfirmReservation.php          + ConfirmReservationHandler.php
    │   │   └── CancelReservation.php           + CancelReservationHandler.php
    │   └── Query/
    │       ├── GetReservation.php              + GetReservationHandler.php
    │       ├── ListReservations.php            + ListReservationsHandler.php
    │       └── ReservationView.php             # read model returned to the UI
    ├── Infrastructure/
    │   └── Persistence/Doctrine/
    │       ├── DoctrineReservationRepository.php
    │       ├── Mapping/Reservation.orm.xml     # mapping lives here, not in the domain
    │       └── Type/ReservationIdType.php      # only if ids become UUIDs
    └── UI/
        └── Http/
            ├── ReservationController.php
            ├── Request/ReservationInput.php    # today's src/Dto/ReservationInput.php
            └── DomainExceptionListener.php     # domain exception → 404 / 409 / 422
```

> Laravel comparison: Laravel has no official equivalent; this is closer to the
> "modules"/"domains" folder layouts some Laravel teams adopt by hand. In Symfony,
> the container's autowiring makes it cheap: any class under `src/` is a service,
> so moving classes needs very little configuration.

## 3. What the domain model will look like

The rules move **into** the aggregate. No public setters; methods are named after
business actions, and each one protects its own invariant.

```php
final class Reservation
{
    private function __construct(
        private ReservationId $id,
        private CustomerName $customerName,
        private Email $email,
        private \DateTimeImmutable $reservationDate,
        private ReservationStatus $status,
        private \DateTimeImmutable $createdAt,
    ) {}

    public static function book(ReservationId $id, CustomerName $name, Email $email,
                                \DateTimeImmutable $date, \DateTimeImmutable $now): self
    {
        if ($date <= $now) {
            throw ReservationDateInThePast::create($date);
        }

        return new self($id, $name, $email, $date, ReservationStatus::Pending, $now);
    }

    public function confirm(): void
    {
        if (ReservationStatus::Pending !== $this->status) {
            throw InvalidStatusTransition::from($this->status, ReservationStatus::Confirmed);
        }
        $this->status = ReservationStatus::Confirmed;
    }

    public function cancel(): void { /* allowed from Pending or Confirmed */ }

    public function changeDetails(CustomerName $name, Email $email,
                                  \DateTimeImmutable $date, \DateTimeImmutable $now): void
    { /* not allowed when Cancelled; new date must be in the future */ }
}
```

The status rules could live on the enum to make them readable in one place:

```php
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending   => \in_array($to, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => self::Cancelled === $to,
            self::Cancelled => false,
        };
    }
}
```

"Now" is passed in (from Symfony's `ClockInterface`, already installed via
`symfony/clock`) so the domain is deterministic and testable with `MockClock`.

### Where validation lives after the migration

| Rule | Where | Why |
|---|---|---|
| JSON shape, required fields, types | UI request DTO (`#[Assert\...]` + `#[MapRequestPayload]`) | Fast, user-friendly 422 errors |
| Email format, name not blank | Value objects (`Email`, `CustomerName`) | Must hold no matter who creates a reservation (API, CLI, import) |
| Date in the future, status transitions | Aggregate (`Reservation`) | They are business rules |

Yes, some rules appear twice (DTO and value object). That's normal in DDD: the
DTO gives nice HTTP errors, the domain guarantees correctness.

## 4. Decisions to make before starting

| # | Decision | Options | Recommendation |
|---|---|---|---|
| D1 | Use cases as… | (a) Messenger command/query buses with `#[AsMessageHandler]` (b) plain application service classes | **(a)** — idiomatic Symfony, gives free transaction middleware, and is common interview material (CQRS). Synchronous, not async. |
| D2 | Doctrine mapping | (a) XML mapping in Infrastructure (b) keep PHP attributes on the domain class | **(a)** for a "pure" domain; (b) is a common pragmatic compromise — know how to argue both. |
| D3 | Identifier | (a) keep auto-increment `int` (b) UUID generated in the domain (`symfony/uid`) | **(b)** — the aggregate has its identity from creation (no "id is null until flush"). The API is not public yet, so changing it now is cheap. Needs a migration. |
| D4 | Value objects in DB | Doctrine **embeddables** vs custom DBAL types vs mapping to scalars | Enum: `enumType` on the column (no schema change). `Email`/`CustomerName`: embeddables keep the same columns if configured with `columnPrefix: false`. |
| D5 | Enforce the layers | `deptrac` (static rules) vs code review only | **deptrac** — a CI-checkable rule that Domain imports nothing from Symfony/Doctrine. |

## 5. Migration steps

Each phase ends with **all existing HTTP tests green**. The HTTP tests are the safety
net: they describe the API from the outside, so they must keep passing unchanged
(except where a decision changes the contract, e.g. D3).

### Phase 0 — Safety net
- [ ] Commit the current working state (nothing is committed yet).
- [ ] Run `php bin/phpunit` → all green; this is the baseline.
- [ ] Optional: install `phpstan/phpstan` to catch type errors during the moves.

### Phase 1 — Skeleton and configuration
- [ ] Create the `src/Booking/{Domain,Application,Infrastructure,UI}` folders.
- [ ] Doctrine: point the `App` mapping at `src/Booking/Infrastructure/Persistence/Doctrine/Mapping` (type `xml`) — or keep `attribute` if D2 = (b).
- [ ] Messenger: add `command.bus` (with `doctrine_transaction` middleware) and `query.bus` in `config/packages/messenger.yaml`.
- [ ] `services.yaml`: nothing to add — autowiring covers `src/Booking`. Only exclude the Domain from service registration if desired (entities/VOs aren't services).

### Phase 2 — Domain model (pure PHP)
- [ ] `ReservationStatus` enum, `CustomerName`, `Email`, `ReservationId` value objects.
- [ ] `Reservation` aggregate with `book()`, `confirm()`, `cancel()`, `changeDetails()`, getters only.
- [ ] Domain exceptions.
- [ ] `ReservationRepository` interface: `save()`, `get(ReservationId)` (throws `ReservationNotFound`), `all()`.
- [ ] **Unit tests** in `tests/Booking/Domain` extending plain `PHPUnit\Framework\TestCase` — no kernel, no database, milliseconds to run. Move the "cancelled can't be confirmed" test here.

### Phase 3 — Persistence (Infrastructure)
- [ ] XML mapping for the aggregate (embeddables for VOs, `enumType` for status).
- [ ] `DoctrineReservationRepository` implementing the domain interface. Use `#[AsAlias]` only if autowiring can't pick the implementation on its own (with one implementation it can).
- [ ] `bin/console doctrine:schema:validate` → if D3 = keep int and VOs map to the same columns, **no migration** should be needed. Confirm with `make:migration` (must be empty).
- [ ] If D3 = UUID: `make:migration`, review it, `doctrine:migrations:migrate` (dev and test).

### Phase 4 — Application layer
- [ ] One command + handler per write use case; one query + handler per read.
- [ ] Handlers load via the repository, call the aggregate method, `save()`. No `flush()` in handlers: the `doctrine_transaction` middleware flushes/commits once per command.
- [ ] Queries return `ReservationView` (a readonly DTO), never the aggregate.
- [ ] Kernel tests (`KernelTestCase`) for the handlers, replacing `tests/Service/ReservationServiceTest.php`.

### Phase 5 — UI layer
- [ ] Move the controller to `Booking/UI/Http`; it builds a command/query from the request DTO and dispatches it.
- [ ] `DomainExceptionListener` (`#[AsEventListener]` on `kernel.exception`): `ReservationNotFound` → 404, `InvalidStatusTransition` → 409, `ReservationDateInThePast`/invalid VO → 422.
- [ ] Controller returns `ReservationView` → the API shape is now an explicit contract, decoupled from the table.
- [ ] Run the HTTP tests unchanged → green.

### Phase 6 — Remove the old code
- [ ] Delete `src/Entity`, `src/Service`, `src/Repository`, `src/Dto`, `src/Controller/ReservationController.php`.
- [ ] `bin/console lint:container`, `debug:router`, full test run.

### Phase 7 — Hardening (optional, good interview material)
- [ ] `deptrac` with rules: Domain → nothing; Application → Domain; Infrastructure/UI → anything inward.
- [ ] Domain events (`ReservationConfirmed`) recorded by the aggregate and dispatched after commit — e.g. to send a confirmation email via Messenger async.
- [ ] Consider Symfony **Workflow** if statuses grow beyond three.

## 6. Getting the backend ready for the React / Next.js frontend

Do these after Phase 5, before starting the frontend:

- [ ] **CORS**: `composer require nelmio/cors-bundle`, allow the Next.js dev origin (`http://localhost:3000`) via `CORS_ALLOW_ORIGIN` in `.env.local`.
- [ ] **Stable error format**: every 4xx returns the same JSON shape (Symfony already produces RFC 7807 `application/problem+json` — keep it consistent for validation errors too, including the field name that failed).
- [ ] **Explicit response contract**: `ReservationView` fields documented (consider `nelmio/api-doc-bundle` for an OpenAPI spec; the frontend can generate TypeScript types from it).
- [ ] **Pagination/filtering** on `GET /api/reservations` (`#[MapQueryString]` with a query DTO).
- [ ] **Auth** (if needed): decide session cookie vs token (JWT, `lexik/jwt-authentication-bundle`) — this changes how Next.js calls the API.

## 7. Talking points for the interview

- **Anemic vs rich domain model**: why moving `assertStatus()` from the service into `Reservation::confirm()` matters.
- **Aggregate**: the consistency boundary; the only entry point to change a reservation.
- **Value objects**: immutable, compared by value, valid by construction.
- **Repository interface in the domain, implementation in infrastructure**: dependency inversion.
- **CQRS with Messenger**: commands change state and return nothing; queries return views.
- **Where flush happens**: in DDD + Messenger, once per command via `doctrine_transaction` middleware — ties back to the Unit of Work.
- **When DDD is overkill**: for a CRUD this size it's mostly structure; it pays off when rules grow. Being able to say this is a sign of seniority.
