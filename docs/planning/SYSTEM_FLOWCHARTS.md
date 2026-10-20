# Private Pickleball Court Reservation System Flowcharts

Legend: solid line = main flow · dotted line = background process

## Customer Flow

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#EFF6FF","primaryColor":"#DBEAFE","primaryTextColor":"#172554","primaryBorderColor":"#2563EB","lineColor":"#475569","secondaryColor":"#DCFCE7","tertiaryColor":"#FEF3C7","fontFamily":"Arial"}}}%%
flowchart TD
    A([Buksan ang website]) --> B{May account na?}

    B -- Wala --> C[Mag-register]
    C --> D{Valid ang impormasyon?}
    D -- Hindi --> E[Ipakita ang validation errors]
    E --> C
    D -- Oo --> F[(I-save ang account)]
    F --> G[Mag-login]

    B -- Meron --> G
    G --> H{Valid ang credentials?}
    H -- Hindi --> I[Ipakita ang login error]
    I --> G
    H -- Oo --> J[Customer Dashboard]

    J --> K[Tingnan ang Availability Calendar]
    K --> L[Pumili ng pickleball court, date, oras at duration]
    L --> M{Available ang schedule?}

    M -- Hindi --> N[Pumili ng ibang schedule]
    N --> K

    M -- Oo --> O[Review reservation at presyo]
    O --> P[Submit reservation]
    P --> Q[(Pending reservation at temporary slot hold)]

    Q --> R{Payment method}

    R -- Online --> S[Magpunta sa PayMongo Checkout]
    S --> T{Successful ang payment?}
    T -- Hindi --> U[Pending hanggang payment deadline]
    U --> U1{Pagkatapos ng maximum retry?}
    U1 -- Hindi --> S
    U1 -- Oo --> U2[Ipakita ang error at hintayin ang deadline]
    U2 --> V{Lumampas na sa deadline?}

    T -- Oo --> Y[Hintayin ang payment verification]

    R -- On-site --> Z[Basahin ang payment instructions]
    Z --> AA[Magbayad sa facility]
    AA --> AB[Hintayin ang admin verification]

    V -- Oo --> W[Reservation expired]
    W --> X[Available muli ang schedule]
    X --> K
    V -- Hindi --> U2

    Y --> AC[Reservation confirmed]
    AB --> AC

    AC --> AD[Makita ang confirmation]
    AD --> AE[Download receipt]
    AC --> AF[Matanggap ang confirmation email]
    AE --> AG[View Reservation History]
    AF --> AG
    AG --> AH([Tapos])

    classDef startEnd fill:#2563EB,color:#FFFFFF,stroke:#1D4ED8,stroke-width:2px;
    classDef action fill:#DBEAFE,color:#172554,stroke:#60A5FA;
    classDef decision fill:#FEF3C7,color:#713F12,stroke:#F59E0B;
    classDef success fill:#DCFCE7,color:#14532D,stroke:#22C55E;
    classDef error fill:#FEE2E2,color:#7F1D1D,stroke:#EF4444;
    classDef data fill:#EDE9FE,color:#4C1D95,stroke:#8B5CF6;

    class A,AH startEnd;
    class C,G,J,K,L,O,P,S,U2,Z,AA,AB,AD,AE,AF,AG action;
    class B,D,H,M,R,T,U1,V decision;
    class AC success;
    class E,I,N,U,W,X error;
    class F,Q data;
```

## Admin Flow

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#F0FDF4","primaryColor":"#DCFCE7","primaryTextColor":"#14532D","primaryBorderColor":"#16A34A","lineColor":"#475569","secondaryColor":"#DBEAFE","tertiaryColor":"#FEF3C7","fontFamily":"Arial"}}}%%
flowchart TD
    A([Admin Login]) --> B[Enter email at password]
    B --> C{Valid admin account?}

    C -- Hindi --> D[Access denied]
    D --> A

    C -- Oo --> E[Admin Dashboard]
    E --> F{Pumili ang module}

    F --> G[Pickleball Court Management]
    G --> G1[Add, edit o delete pickleball court]
    G1 --> G2[Set rate, location at availability]
    G2 --> G5{May kaugnayan na reservation?}
    G5 -- Oo --> G6[Archive ang pickleball court]
    G5 -- Hindi --> G7[Confirm deletion]
    G6 --> Z
    G7 --> Z

    F --> H[Schedule Management]
    H --> H1[Add maintenance o event block]
    H1 --> H2{May conflict sa confirmed booking o pending hold?}
    H2 -- Oo --> H3[Reject block o ayusin ang booking]
    H3 --> H1
    H2 -- Wala --> Z

    F --> I[Reservation Management]
    I --> I1[View pending at confirmed reservations]
    I1 --> I2{Admin action}
    I2 -- Update --> I3[Validate reservation changes]
    I2 -- Cancel --> I4[Confirm cancellation]
    I3 --> Z
    I4 --> Z

    F --> J[On-site Payment Verification]
    J --> J1[Hanapin ang reservation]
    J1 --> J2[Check amount at payment details]
    J2 --> J3{Valid ang payment?}
    J3 -- Hindi --> J4[Reject o panatilihing pending]
    J4 --> E
    J3 -- Oo --> J5[(Record verified payment)]
    J5 --> J6[Confirm reservation]
    J6 --> J7[Generate receipt at queue email]
    J7 --> Z

    F --> K[Payment Records]
    K --> K1[Review method, amount, date at status]
    K1 --> E

    F --> L[Reports]
    L --> L1[Select date range]
    L1 --> L2[Generate reservations, utilization at revenue report]
    L2 --> E

    Z --> M[(Create audit log)]
    M --> E
    E --> N[Logout]
    N --> O([Tapos])

    classDef startEnd fill:#16A34A,color:#FFFFFF,stroke:#15803D,stroke-width:2px;
    classDef action fill:#DCFCE7,color:#14532D,stroke:#4ADE80;
    classDef decision fill:#FEF3C7,color:#713F12,stroke:#F59E0B;
    classDef warning fill:#FEE2E2,color:#7F1D1D,stroke:#EF4444;
    classDef data fill:#DBEAFE,color:#1E3A8A,stroke:#3B82F6;

    class A,O startEnd;
    class B,E,G,G1,G2,H,H1,I,I1,I3,I4,J,J1,J2,J6,J7,K,K1,L,L1,L2,N action;
    class C,F,G5,H2,I2,J3 decision;
    class D,H3,J4 warning;
    class Z,J5,M data;
```

## Internal System Flow

```mermaid
%%{init: {"theme":"base","themeVariables":{"background":"#FAF5FF","primaryColor":"#EDE9FE","primaryTextColor":"#4C1D95","primaryBorderColor":"#7C3AED","lineColor":"#475569","secondaryColor":"#DBEAFE","tertiaryColor":"#FEF3C7","fontFamily":"Arial"}}}%%
flowchart TD
    A([Request received]) --> TLS[[HTTPS / TLS]]
    TLS --> B{Authenticated?}

    B -- Hindi --> C[Return unauthorized response]
    B -- Oo --> D{Authorized ang role?}

    D -- Hindi --> E[Return forbidden response]
    D -- Oo --> F[Validate input]

    F --> G{Valid ang data?}
    G -- Hindi --> H[Return validation errors]
    G -- Oo --> I{Anong request?}

    I -- Reservation --> J[Check court rules at operating hours]
    J --> K[Calculate start time, end time at price]
    K --> L{May overlapping reservation, hold, o court block?}

    L -- Oo --> M[Reject unavailable schedule]
    L -- Wala --> N[Start transaction, lock the court row]
    N --> O[(Create pending reservation)]
    O --> P[Set payment deadline]
    P --> Q[Commit transaction]
    Q --> R{Payment method}

    R -- Online --> S[Create PayMongo Checkout Session]
    S --> T[Return checkout URL sa customer]

    R -- On-site --> U[Return payment instructions]
    U --> V[Wait for admin verification]

    I -- CRUD operation --> W[Create, read, update o archive record]
    W --> X[(Save database changes)]
    X --> Y[(Create audit log)]
    Y --> Z[Return successful response]

    T --> AA[PayMongo sends webhook]
    AA --> AB{Valid webhook signature?}
    AB -- Hindi --> AC[Reject at log webhook]
    AB -- Oo --> AD{Processed na ang event ID?}
    AD -- Oo --> AE[Return success without duplicate changes]
    AD -- Hindi --> AF{Tama ang amount, reference at currency?}

    AF -- Hindi --> AG[Flag for manual review]
    AF -- Oo --> AH{Active pa ang reservation hold?}

    AH -- Hindi --> AG
    AH -- Oo --> AI[Start payment transaction]

    V --> AJ{Admin verified payment?}
    AJ -- Hindi --> AK[Keep pending o reject]
    AK --> AK1{Magtatapos na ba ang deadline?}
    AK1 -- Hindi --> V
    AK1 -- Oo --> AL[(Mark reservation expired)]
    AJ -- Oo --> AI

    AI --> AM[(Record payment)]
    AM --> AN[(Mark reservation confirmed)]
    AN --> AO[(Create immutable receipt)]
    AO --> AP[(Store processed payment event)]
    AP --> AQ[Commit transaction]

    AQ --> AR[Background worker sends email]
    AR --> AS{Email sent?}
    AS -- Hindi --> AT[Schedule retry]
    AT --> AR
    AS -- Oo --> AU[Mark email delivered]

    AP --> AV[Make receipt available for download]
    AU --> AW([Process completed])
    AV --> AW

    P -. scheduler tick kada minuto .-> AX[Find unpaid expired reservations]
    AX --> AY[(Mark reservations expired)]
    AY --> AZ[Release held schedules]

    classDef startEnd fill:#7C3AED,color:#FFFFFF,stroke:#6D28D9,stroke-width:2px;
    classDef process fill:#EDE9FE,color:#4C1D95,stroke:#A78BFA;
    classDef decision fill:#FEF3C7,color:#713F12,stroke:#F59E0B;
    classDef error fill:#FEE2E2,color:#7F1D1D,stroke:#EF4444;
    classDef data fill:#DBEAFE,color:#1E3A8A,stroke:#3B82F6;
    classDef success fill:#DCFCE7,color:#14532D,stroke:#22C55E;
    classDef scheduler fill:#FFEDD5,color:#7C2D12,stroke:#F97316;
    classDef security fill:#FCE7F3,color:#831843,stroke:#DB2777,stroke-width:2px;

    class A,AW startEnd;
    class F,J,K,N,P,Q,S,T,U,V,W,Z,AA,AI,AR,AV process;
    class B,D,G,I,L,R,AB,AD,AF,AH,AJ,AK1,AS decision;
    class C,E,H,M,AC,AG,AK error;
    class O,X,Y,AL,AM,AN,AO,AP,AY data;
    class AU success;
    class AX,AY,AZ scheduler;
    class TLS security;
```

## What changed and why

| Fix | Reason |
| --- | --- |
| Checkout retry limit (`U1`) | The original looped back to PayMongo forever with no limit. |
| Admin block checks pending holds | Original checked confirmed bookings only, so an admin could block a slot a paying customer was holding. |
| Delete with confirmation (`G5`-`G7`) | The instructor requires Delete with a confirmation step. Original had no delete at all. |
| Audit log after payment verification | Payment verification is security-sensitive and must be traceable. |
| On-site rejection path continues (`AK1`) | Original ended in a dead end with no way back. |
| Scheduler connected (`AX`) | Expiry runs off the payment deadline, so it is shown as triggered by it. |
| HTTPS/TLS step | Required by the security section of the paper. |

## Implementation status after the updated paper

| Paper flow | Current system behavior |
| --- | --- |
| Customer availability and booking | Implemented: operating rules, pending holds, PostgreSQL overlap protection, and customer-owned reservation history. |
| Online payment | PayMongo Hosted Checkout QRPh is the active provider flow. The signed webhook and server-side checkout verification confirm payment; browser redirects do not. |
| On-site payment | Implemented: the reservation stays pending until an administrator verifies the payment; confirmation issues the same receipt flow. |
| Admin court availability management | Implemented: court CRUD plus schedule blocks for maintenance, repairs, and private events. Blocks are checked by the same availability service used by customer bookings. |
| Receipt and email | Receipt is generated only after confirmation and is available for authorized download. Confirmation email dispatch is queued after the database transaction. |
| Expiration | Unpaid holds are expired by the scheduler and ignored by availability checks even if the scheduler is delayed. |
| Reports | Implemented: filtered reservation, court utilization, and verified-payment revenue reports. |
