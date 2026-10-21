# Private Pickleball Court Reservation System — Simplified Flowcharts

These presentation diagrams keep only the decisions a user needs to explain. Detailed validation, database constraints, webhook checks, and retry behavior are documented in the technical paper.

## Customer flow

```mermaid
flowchart TD
    A([Open website]) --> B{Has account?}
    B -- No --> C[Register]
    C --> D[Log in]
    B -- Yes --> D
    D --> E[View courts and availability]
    E --> F[Choose court, date, time, and duration]
    F --> G{Slot available?}
    G -- No --> E
    G -- Yes --> H[Review price and submit reservation]
    H --> I{Payment method}
    I -- Online --> J[Pay through PayMongo QRPh]
    I -- On-site --> K[Pay at facility]
    J --> L[Payment verified]
    K --> M[Admin verifies payment]
    L --> N[Reservation confirmed]
    M --> N
    N --> O[Receipt and email sent]
    O --> P([View reservation history])
```

## Admin flow

```mermaid
flowchart TD
    A([Admin login]) --> B{Valid admin?}
    B -- No --> A
    B -- Yes --> C[Admin dashboard]
    C --> D{Choose module}
    D --> E[Manage pickleball courts]
    D --> F[Manage schedule blocks]
    D --> G[Review reservations]
    D --> H[Verify on-site payments]
    D --> I[View payments]
    D --> J[Generate reports]
    E --> K[Save changes and audit action]
    F --> K
    G --> K
    H --> K
    I --> K
    J --> K
    K --> C
    C --> L([Log out])
```

## System and payment flow

```mermaid
flowchart TD
    A([Request]) --> B[Authenticate and authorize]
    B --> C[Validate input]
    C --> D{Valid and available?}
    D -- No --> E[Return error]
    D -- Yes --> F[(Create pending reservation and payment)]
    F --> G{Online payment?}
    G -- Yes --> H[Create PayMongo checkout]
    H --> I[PayMongo sends signed webhook]
    I --> J{Signature, amount, reference, and status valid?}
    J -- No --> K[Flag for review]
    J -- Yes --> L[Confirm payment and reservation]
    G -- No --> M[Wait for admin verification]
    M --> N{Verified?}
    N -- No --> O[Keep pending or reject]
    N -- Yes --> L
    L --> P[(Create one receipt)]
    P --> Q[Queue confirmation email]
    Q --> R([Complete])
    F -. deadline .-> S[(Expire unpaid hold)]
```

## Legend

- Rounded boxes are start/end points.
- Diamond boxes are decisions.
- Database cylinders are saved records.
- Dashed arrows show the background expiration process.
