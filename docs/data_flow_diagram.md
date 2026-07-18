# EvaTrack – Data Flow Diagrams (DFD)

This document contains the Data Flow Diagrams (Context Diagram / Level 0, and Level 1) for the EvaTrack System. You can render these diagrams using [Mermaid Live Editor](https://mermaid.live).

---

## Level 0: Context Diagram

The Context Diagram shows the entire EvaTrack system as a single process interacting with external entities (Users).

```mermaid
flowchart LR
    %% External Entities (Rectangles)
    SA["Super Admin"]
    EA["Evacuation Admin"]
    CP["Center Personnel"]
    PU["Public User"]
    RES["Resident / Evacuee"]

    %% Main System Process (Circle)
    SYS(("EvaTrack\nSystem"))

    %% Data Flows
    SA -->|System configs, Event/Center details, User accounts| SYS
    EA -->|Event/Center operations, Household verification| SYS
    CP -->|QR Scans, Manual evacuation logs, Resource requests, Issues| SYS
    PU -->|Views active events, Center locations| SYS
    
    SYS -->|System reports, Analytics, Dashboards| SA
    SYS -->|Event updates, Capacity alerts| EA
    SYS -->|Evacuee tracking status, Approvals| CP
    SYS -->|Push Notifications, SMS alerts, Announcements| RES
    RES -->|Location data, Evacuation status updates| SYS

    %% Styling
    classDef entity fill:#3498db,stroke:#2980b9,stroke-width:2px,color:#fff
    classDef system fill:#2ecc71,stroke:#27ae60,stroke-width:4px,color:#fff
    class SA,EA,CP,PU,RES entity
    class SYS system
```

---

## Level 1: Data Flow Diagram

The Level 1 DFD breaks down the main EvaTrack system into its primary sub-processes and shows the data stores (databases) they interact with.

```mermaid
flowchart TB
    %% External Entities
    SA["Super Admin / Evac Admin"]
    CP["Center Personnel"]
    RES["Resident / Evacuee"]

    %% Processes (Rounded Rectangles)
    P1("1.0\nAuthentication\n& User Mgt")
    P2("2.0\nHousehold\nRegistration")
    P3("3.0\nEvent & Center\nManagement")
    P4("4.0\nEvacuation\nOperations")
    P5("5.0\nResources & Issues\nManagement")
    P6("6.0\nNotification\nEngine")
    P7("7.0\nAnalytics\nReporting")

    %% Data Stores (Cylinders)
    D1[(D1: Users DB)]
    D2[(D2: Households DB)]
    D3[(D3: Events & Centers DB)]
    D4[(D4: Evac Records DB)]
    D5[(D5: Ops & Resources DB)]

    %% 1.0 Auth Flows
    SA -->|Credentials, Roles| P1
    CP -->|Credentials| P1
    P1 <-->|Verify / Update| D1

    %% 2.0 Household Flows
    SA -->|Household Data| P2
    CP -->|Household Data| P2
    P2 -->|Save Profiles| D2

    %% 3.0 Event/Center Flows
    SA -->|Create Events, Setup Centers| P3
    P3 -->|Store Details| D3

    %% 4.0 Evacuation Operations Flows
    CP -->|QR Scans, Check-in/out| P4
    P4 <-->|Verify Household| D2
    P4 <-->|Check Center Capacity| D3
    P4 -->|Log Evacuation| D4

    %% 5.0 Resources & Issues Flows
    CP -->|Report Issues, Request Items| P5
    SA -->|Approve Requests, Update Status| P5
    P5 -->|Update Ops Data| D5

    %% 6.0 Notification Flows
    SA -->|Trigger Alerts| P6
    P3 -->|Event Triggers| P6
    P6 <-->|Fetch Contact Info| D2
    P6 -->|Push/SMS Alerts| RES

    %% 7.0 Analytics Flows
    D4 -->|Evacuation Stats| P7
    D3 -->|Capacity Stats| P7
    D5 -->|Issue/Resource Stats| P7
    P7 -->|Generate Dashboards & Exports| SA

    %% Styling
    classDef entity fill:#3498db,stroke:#2980b9,stroke-width:2px,color:#fff
    classDef process fill:#e67e22,stroke:#d35400,stroke-width:2px,color:#fff
    classDef datastore fill:#9b59b6,stroke:#8e44ad,stroke-width:2px,color:#fff
    
    class SA,CP,RES entity
    class P1,P2,P3,P4,P5,P6,P7 process
    class D1,D2,D3,D4,D5 datastore
```
