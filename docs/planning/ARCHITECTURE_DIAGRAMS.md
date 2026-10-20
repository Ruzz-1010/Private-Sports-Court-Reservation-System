# Architecture and Use Case Diagrams

Private Pickleball Court Reservation System — Barangay San Jose, Puerto Princesa City, Palawan

Supports paper sections 2.3 (use cases), 3.2 (block diagram), and 3.3 (sequence
diagrams). Render the Mermaid source at <https://mermaid.live>.

---

## 2.3 System Use Case Diagram

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#FFFFFF","primaryColor":"#DBEAFE","primaryTextColor":"#172554","primaryBorderColor":"#2563EB","lineColor":"#475569","fontFamily":"Arial"}}}%%
flowchart LR
    classDef actor fill:#FCE7F3,color:#831843,stroke:#DB2777,stroke-width:2px;
    classDef uc fill:#EFF6FF,color:#172554,stroke:#60A5FA;
    classDef ucA fill:#F0FDF4,color:#14532D,stroke:#4ADE80;

    CUST(["Customer"])
    ADMIN(["Administrator"])
    PROV(["Payment Provider<br/>PayMongo"])

    subgraph M1["Account"]
        U1([Register and log in])
    end

    subgraph M2["Reservation"]
        U2([View availability calendar])
        U3([Reserve a court])
        U4([View reservation history])
    end

    subgraph M3["Payment"]
        U5([Pay online])
        U6([Pay on-site])
        U7([Download receipt])
    end

    subgraph M4["Administration"]
        A1([Manage courts])
        A2([Manage schedules and blocks])
        A3([View and update reservations])
        A4([Verify on-site payment])
        A5([View payment records])
        A6([Generate reports])
    end

    CUST --> U1
    CUST --> U2
    CUST --> U3
    CUST --> U4
    CUST --> U5
    CUST --> U6
    CUST --> U7

    U3 -. "include" .-> U2

    PROV --> U5
    U5 -. "extend" .-> U7

    ADMIN --> A1
    ADMIN --> A2
    ADMIN --> A3
    ADMIN --> A4
    ADMIN --> A5
    ADMIN --> A6

    A4 -. "extend" .-> U7

    class CUST,ADMIN,PROV actor;
    class U1,U2,U3,U4,U5,U6,U7 uc;
    class A1,A2,A3,A4,A5,A6 ucA;
```

### Relationships

| Relationship | Type | Description |
| --- | --- | --- |
| Reserve a court | *include* | Availability is always checked before a reservation is accepted |
| Pay online | *extend* | Adds a receipt once the payment is verified |
| Verify on-site payment | *extend* | Adds a receipt once the administrator verifies payment |

---

## 3.2 Architectural Block Diagram

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#FFFFFF","primaryColor":"#DBEAFE","primaryTextColor":"#172554","primaryBorderColor":"#2563EB","lineColor":"#475569","fontFamily":"Arial"}}}%%
flowchart TB
    classDef ext fill:#FCE7F3,color:#831843,stroke:#DB2777,stroke-width:2px;
    classDef box fill:#EFF6FF,color:#172554,stroke:#3B82F6,stroke-width:2px;
    classDef db fill:#DBEAFE,color:#1E3A8A,stroke:#3B82F6,stroke-width:3px;

    BROWSER(["Customer / Administrator"])

    FE["Frontend<br/>Blade Templates · Tailwind CSS<br/>Alpine.js · FullCalendar"]
    BE["Backend<br/>Laravel Application<br/>Routes · Middleware · Controllers<br/>Services · Policies"]
    DB[("Database Engine<br/>PostgreSQL<br/>users · courts · reservations<br/>payments · receipts")]
    PAY["Payment Processor<br/>PayMongo Hosted Checkout"]
    MAIL["Email Service<br/>SMTP Provider<br/>queued confirmation + receipt PDF"]

    QUEUE[["Queue and Scheduler<br/>background email · hold expiration"]]

    BROWSER -->|"HTTPS"| FE
    FE -->|"HTTP request"| BE
    BE <-->|"parameterized SQL"| DB
    BE -->|"create checkout session"| PAY
    PAY -->|"signed webhook"| BE
    BE -.->|"queued email"| QUEUE
    QUEUE --> MAIL
    MAIL -.->|"confirmation email with receipt PDF"| BROWSER

    class BROWSER,PAY,MAIL ext;
    class FE,BE,QUEUE box;
    class DB db;
```

---

## 3.3 Sequence Diagrams

### Online Payment and Webhook Processing

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#FFFFFF","primaryColor":"#DBEAFE","primaryTextColor":"#172554","primaryBorderColor":"#2563EB","lineColor":"#475569","fontFamily":"Arial"}}}%%
sequenceDiagram
    autonumber
    actor C as Customer
    participant A as Laravel Application
    participant D as PostgreSQL
    participant P as PayMongo

    C->>A: Submit reservation with online payment
    A->>A: Authenticate and validate input
    A->>D: BEGIN, lock court, check for overlap
    alt May overlap with a hold or booking
        D-->>A: Conflict found
        A-->>C: Reject, slot not available
    else No overlap
        A->>D: INSERT reservation (pending, deadline set)
        A->>D: INSERT payment (pending)
        A->>D: COMMIT
        A->>P: Create checkout session (POST /v2/checkout_sessions)
        P-->>A: Checkout URL
        A-->>C: Redirect to PayMongo
        Note over C,P: Wallet credentials go to PayMongo only.

        P->>A: Webhook POST with Paymongo-Signature
        A->>A: Verify HMAC-SHA256 over timestamp.raw_body
        alt Invalid signature
            A-->>P: 400, nothing recorded
        else Valid signature
            A->>A: Reject timestamp outside five-minute replay window
            A->>D: Look up provider event id
            alt Event already processed
                A-->>P: 200, no duplicate work
            else New event
                A->>A: Keep only checkout_session.payment.paid
                A->>D: Find payment by checkout session id
                Note over A,P: The callback payload is never trusted.<br/>The session is re-read from PayMongo over /v1.
                A->>P: GET /v1/checkout_sessions/{id}
                P-->>A: Authoritative status, amount, currency, reference
                A->>A: Compare retrieved values against local attempt
                alt Disagreement or not paid
                    A->>D: payment = requires_review
                    A-->>P: 200, nothing confirmed
                else Agreement and paid
                    A->>D: BEGIN
                    A->>D: UPDATE payment to paid
                    A->>D: UPDATE reservation to confirmed
                    A->>D: INSERT receipt (exactly one)
                    A->>D: INSERT payment event (processed)
                    A->>D: COMMIT
                    A-->>P: 200
                    Note over A,C: Dispatch only after commit.<br/>PDF rendered at send time and attached.
                    A->>A: Queue SendConfirmationEmail
                    A-->>C: Confirmation email with receipt PDF
                end
            end
        end
    end
```

### On-site Payment Verification

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#FFFFFF","primaryColor":"#DCFCE7","primaryTextColor":"#14532D","primaryBorderColor":"#16A34A","lineColor":"#475569","fontFamily":"Arial"}}}%%
sequenceDiagram
    autonumber
    actor C as Customer
    actor ADM as Administrator
    participant A as Laravel Application
    participant D as PostgreSQL

    C->>A: Submit reservation with on-site payment
    A->>D: BEGIN, lock court, check for overlap
    A->>D: INSERT reservation (pending, deadline set)
    A->>D: INSERT payment (pending, on-site)
    A->>D: COMMIT
    A-->>C: Show amount to pay and deadline

    Note over C: Customer pays cash at the facility

    ADM->>A: Verify the on-site payment
    A->>A: Authorize admin role
    A->>D: Load reservation and payment
    alt Amount mismatch or hold expired
        A->>D: Record rejected attempt and audit log
        A-->>ADM: Rejected with reason
    else Valid
        Note over A: ConfirmPayment — the same action<br/>the webhook path calls.
        A->>D: BEGIN
        A->>D: UPDATE payment to paid, record admin
        A->>D: UPDATE reservation to confirmed
        A->>D: INSERT receipt (exactly one)
        A->>D: INSERT audit log
        A->>D: COMMIT
        A-->>ADM: Verified, receipt issued
        Note over A,C: Dispatch only after commit, so the two<br/>paths send the identical notification.
        A->>A: Queue SendConfirmationEmail
        A-->>C: Confirmation email with receipt PDF
    end
```

---

## Notes for the paper

- Section 2.3 asks for how the actors interact with the system modules. The source
  document names three actors: Customer, Administrator, and Payment Provider.
- Section 3.2 asks for a map of the Frontend, Backend, Database Engine, Payment
  Processor, and Email Service. Those five are the main blocks.
- Section 3.3 asks for the step-by-step API communication for payment checkout and
  webhook processing, which is the first sequence diagram.
- Hold expiration, late payment reconciliation, and email retry are implemented but
  are described in text in section 4.3 rather than given separate diagrams.
- These diagrams describe the **built** system, not a target design. They were
  reconciled against the code after the PayMongo integration was verified
  end to end. Three things were corrected when that happened:
  - The webhook sequence omitted the independent `GET /v1/checkout_sessions/{id}`
    retrieval, jumping straight from validation to the confirmation transaction.
    That retrieval is the property the design actually depends on, because the
    callback payload is never trusted.
  - The on-site sequence omitted the queued confirmation email, which
    `PaymentVerificationController` dispatches after commit using the same
    `ConfirmPayment` action and the same notification as the webhook path.
  - The block diagram still named Mailtrap. Delivery is verified against a live
    SMTP provider, and the email now carries the rendered receipt PDF as an
    attachment rather than only linking to it.
- Regenerate the PNG exports from mermaid.live after editing this file. The paper
  embeds `architecture-block-diagram.png`, `sequence-payment-webhook.png`, and
  `sequence-onsite-payment.png`, so a stale export silently contradicts the text.
