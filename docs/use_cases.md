# EvaTrack – Use Case Diagram & Detailed Use Cases

**Project Title:** EVATRACK: Streamlined Evacuation Processes and Coordination Platform Integrating SafeTrack Demographics and ResQperation Response Support  
**Proponents:** Klint M. Ruales, Danica Gelbolingo, Anna Rhea Villadolid, Vhenz Cernal  
**Institution:** School of Computer Studies, University of San Jose-Recoletos (USJ-R)  
**Adviser:** Mr. Stefan A.D. Flores | **Project Coordinator:** Mr. Roderick A. Bandalan  
**Date:** October 2026 (Version 1.1 - Verified Against Production Architecture)

---

## 1. System Overview & Must-Haves Alignment

EvaTrack streamlines evacuation operations, real-time center occupancy tracking, demographic analytics, in-center incident triage, and multi-agency response coordination by integrating with:
1. **SafeTrack System:** Ingests household demographic records solely for on-site personnel identity verification, demographic breakdown, and needs prioritization. This sensitive demographic data is strictly isolated from public/household view.
2. **ResQperation System:** Interoperates for emergency response logistics dispatch (routing resource shortages and personnel demands) and multi-channel push notification broadcasting to citizens.

### Core Must-Have System Capabilities:
1. **SafeTrack Demographics Integration:** Rapid ingestion and verification of family rosters via QR code and manual fallback without exposing data to residents.
2. **Real-Time Evacuation Status Tracking:** Discrete tracking of households and individuals (`Evacuated` [Status ID: 2] vs. `Not Evacuated` [Status ID: 1]).
3. **Automated Shelter Capacity & Room Allocation:** Automatic real-time increments and decrements of shelter occupancy and remaining bed capacity upon admission and checkout.
4. **Demographic Intelligence & Vulnerability Analytics:** Automated aggregation of children (0–17), adults (18–59), elderly (60+), pregnant women, Persons with Disabilities (PWD), gender ratios, and affected populations.
5. **In-Center Incident & Health Condition Reporting:** Real-time triage, severity assessment (`low`, `medium`, `high`, `critical`), photographic evidence uploads, and Jira-style Kanban monitoring of shelter issues.
6. **Resource Logistics & ResQperation Routing:** Automated demand logging and status transition for food, potable water, medical kits, and response personnel.
7. **Multi-Channel Emergency Alerts:** Scheduled, instant, and recurring broadcast dispatch through cellular SMS (for offline residents) and ResQperation push notifications.

---

## 2. System Actors

| Actor | Role Type | Description & System Scope |
|-------|-----------|-----------------------------|
| **Super Admin** | Primary System Actor | Dedicated solely to system-level user account administration. **Exclusively authorized to register new user accounts** (`POST /api/users`), assign roles, and activate/deactivate accounts. |
| **Evacuation Admin** | Primary System Actor | Coordinates center operations and emergency directives. Declares disaster events, configures evacuation centers, assigns personnel to centers, broadcasts emergency SMS and push alerts, monitors resource logistics and center issues, and analyzes demographic reports. |
| **Evacuation Personnel** | Primary System Actor | Stationed at an assigned evacuation center. Performs on-site intake verification via QR scanning or demographic search, admits households, configures accommodation units, allocates living spaces, tracks member stay and departure checkout, reports facility issues, and submits resource requests. |
| **Households / Residents** | Secondary Actor (Passive) | Displaced citizens. Possess no login credentials. View shelter availability on the public map portal, receive SMS/Push warnings, and present official SafeTrack QR codes or identification upon arriving at a center. |
| **SafeTrack System** | External Integration | Authoritative demographic repository. Provides pre-registered household rosters, addresses, and vulnerability tags for verification and analytics. |
| **ResQperation System** | External Integration | Emergency response coordination platform. Receives forwarded resource/personnel aid requests from EvaTrack and serves as the push notification delivery gateway for smartphone alerts. |

---

## 3. Core Use Case Diagram (Black & White Elements with Color-Coded Arrows)

```mermaid
flowchart LR
    %% LEFT: Human Actors
    subgraph ACTORS ["System Actors"]
        direction TB
        SA["👤 Super Admin"]:::bwNode
        EA["👤 Evacuation Admin"]:::bwNode
        EP["👤 Evacuation Personnel"]:::bwNode
        HH["📱 Resident / Household (Passive)"]:::bwNode
    end

    %% CENTER: EvaTrack Core Platform
    subgraph EVATRACK ["EVATRACK PLATFORM"]
        direction TB
        
        %% Super Admin
        UC_USERS(["Manage User Accounts"]):::bwUC
        
        %% Evacuation Admin
        UC_EVENTS(["Manage Disaster Events"]):::bwUC
        UC_CENTERS(["Manage Centers & Assign Personnel"]):::bwUC
        UC_ALERTS(["Broadcast Emergency Alerts (SMS & Push)"]):::bwUC
        UC_ANALYTICS(["View & Export Evacuation Analytics"]):::bwUC

        %% Shared Collaborative Operations
        UC_ISSUES(["Report & Monitor Center Issues"]):::bwUC
        UC_RESOURCES(["Request & Track Logistics Assistance"]):::bwUC

        %% Evacuation Personnel
        UC_VERIFY(["Verify & Admit Households (QR / Manual)"]):::bwUC
        UC_UNITS(["Configure Units & Allocate Families"]):::bwUC
        UC_CHECKOUT(["Track Member Stay & Process Checkout"]):::bwUC

        %% Resident Public View
        UC_PORTAL(["View Public Evacuation Map"]):::bwUC
    end

    %% RIGHT: External Partner Systems
    subgraph EXTERNAL ["External Systems"]
        direction TB
        ST["🌐 SafeTrack System"]:::bwNode
        RQ["🌐 ResQperation System"]:::bwNode
    end

    %% Super Admin Connections (Purple)
    SA --> UC_USERS

    %% Evacuation Admin Connections (Blue)
    EA --> UC_EVENTS
    EA --> UC_CENTERS
    EA --> UC_ALERTS
    EA --> UC_ANALYTICS
    EA --> UC_ISSUES
    EA --> UC_RESOURCES

    %% Evacuation Personnel Connections (Green)
    EP --> UC_VERIFY
    EP --> UC_UNITS
    EP --> UC_CHECKOUT
    EP --> UC_ISSUES
    EP --> UC_RESOURCES

    %% Resident Connections (Teal / Dashed)
    HH -.-> UC_PORTAL
    HH -.-> UC_ALERTS
    HH -.-> UC_VERIFY

    %% External System Connections (Center to Right)
    UC_VERIFY -->|"Demographics Query"| ST
    UC_RESOURCES -->|"Logistics Demand"| RQ
    UC_ALERTS -->|"Push Notification Dispatch"| RQ

    %% Node Styling (Clean Black and White / Grayscale)
    classDef bwNode fill:#ffffff,stroke:#0f172a,stroke-width:2px,color:#0f172a,font-weight:bold;
    classDef bwUC fill:#ffffff,stroke:#334155,stroke-width:1.8px,color:#0f172a;

    style ACTORS fill:#f8fafc,stroke:#94a3b8,stroke-width:1.5px,stroke-dasharray: 4 4;
    style EVATRACK fill:#ffffff,stroke:#0f172a,stroke-width:2px;
    style EXTERNAL fill:#f8fafc,stroke:#94a3b8,stroke-width:1.5px,stroke-dasharray: 4 4;

    %% Color-Coded Arrows (linkStyle)
    %% Link 0: Super Admin (Purple)
    linkStyle 0 stroke:#9333ea,stroke-width:2.5px;

    %% Links 1-6: Evacuation Admin (Blue)
    linkStyle 1 stroke:#2563eb,stroke-width:2.5px;
    linkStyle 2 stroke:#2563eb,stroke-width:2.5px;
    linkStyle 3 stroke:#2563eb,stroke-width:2.5px;
    linkStyle 4 stroke:#2563eb,stroke-width:2.5px;
    linkStyle 5 stroke:#2563eb,stroke-width:2.5px;
    linkStyle 6 stroke:#2563eb,stroke-width:2.5px;

    %% Links 7-11: Evacuation Personnel (Green)
    linkStyle 7 stroke:#059669,stroke-width:2.5px;
    linkStyle 8 stroke:#059669,stroke-width:2.5px;
    linkStyle 9 stroke:#059669,stroke-width:2.5px;
    linkStyle 10 stroke:#059669,stroke-width:2.5px;
    linkStyle 11 stroke:#059669,stroke-width:2.5px;

    %% Links 12-14: Resident / Public (Teal)
    linkStyle 12 stroke:#0891b2,stroke-width:2px;
    linkStyle 13 stroke:#0891b2,stroke-width:2px;
    linkStyle 14 stroke:#0891b2,stroke-width:2px;

    %% Links 15-17: External Systems (SafeTrack Indigo & ResQperation Rose)
    linkStyle 15 stroke:#4f46e5,stroke-width:2.5px;
    linkStyle 16 stroke:#e11d48,stroke-width:2.5px;
    linkStyle 17 stroke:#e11d48,stroke-width:2.5px;
```

---

## 4. Detailed Use Case Narratives

### Package 1: Authentication & Account Security

#### UC-01: User Login & Session Establishment
* **Actors:** Super Admin, Evacuation Admin, Evacuation Personnel
* **Description:** User authenticates into EvaTrack using registered credentials (User ID or Email) and password, establishing an encrypted session secured by an `HttpOnly` cookie and protected by brute-force rate-limiting.
* **Preconditions:** Account exists and has `is_active = 1`.
* **Postconditions:** Active token generated in `personal_access_tokens`; `HttpOnly`, `SameSite=Lax` `access_token` cookie attached to HTTP response (with Bearer token fallback for native mobile apps); user redirected to role-tailored dashboard.
* **Main Flow:**
  1. User navigates to `/login` and provides identifier (e.g., `EPE-2026-PERS1` or email) and password.
  2. System evaluates rate limiter (`throttle:6,1` on `/api/login`).
  3. System validates input against Bcrypt hash in `users.password`.
  4. System invalidates any prior tokens (`$user->tokens()->delete()`) to guarantee single-session integrity.
  5. System generates personal access token, issues `access_token` in an `HttpOnly`, `SameSite=Lax` cookie with 7-day sliding expiration, loads assigned role (`roles`), cached center pointer (`assigned_center_id`), and current assignment record.
  6. Frontend receives sanitized user profile and navigates to the authorized workspace without storing the auth token in `localStorage`.
* **Alternative Flows:**
  * Rate limit exceeded (more than 6 attempts in 1 min) → HTTP 429 Too Many Requests lockout.
  * Invalid credentials → Rejection prompt with failed attempt warning.
  * Inactive account (`is_active = 0`) → Displays "Account deactivated. Contact Administrator."
* **API Endpoint & DB Tables:** `POST /api/login` | `users`, `roles`, `personal_access_tokens`.

#### UC-02: User Logout
* **Actors:** All Authenticated Users
* **Description:** User terminates their current operational session; the system revokes the active token from the database, expires the `HttpOnly` `access_token` cookie in the client browser, and cleans up client-side memory stores.
* **API Endpoint & DB Tables:** `POST /api/logout` | `personal_access_tokens`.

#### UC-03: View & Update Profile
* **Actors:** All Authenticated Users
* **Description:** Operator updates their personal information (name, contact phone number, profile avatar).
* **API Endpoint & DB Tables:** `GET /api/user`, `PUT /api/user/profile` | `users`.

#### UC-04: Password Change & Security Reset
* **Actors:** All Authenticated Users
* **Description:** Operator updates password by validating their old password or through OTP SMS verification.
* **API Endpoint & DB Tables:** `PUT /api/user/password`, `POST /api/forgot-password/*` | `users`.

---

### Package 2: User & Personnel Management

#### UC-05: List System Users
* **Actors:** Super Admin, Evacuation Admin
* **Description:** Displays a paginated roster of system accounts with role filters, station assignments, and contact details.
* **API Endpoint & DB Tables:** `GET /api/users` | `users`, `roles`, `evacuation_centers`.

#### UC-06: Register User Account (Super Admin Exclusive)
* **Actors:** Super Admin
* **Description:** Super Admin creates new Evacuation Admin or Evacuation Personnel accounts. The system enforces business rules, hashes passwords, maps roles, and generates standard UIDs (`SUP-`, `EAD-`, `EPE-`).
* **Preconditions:** Authenticated user is `super_admin`.
* **Main Flow:**
  1. Super Admin opens User Management modal and inputs First Name, Last Name, Password, Role, Contact Number, and optional Email.
  2. Frontend sends payload to `POST /api/users`.
  3. Backend validates uniqueness of contact/email via `StoreUserRequest`.
  4. `CreateUserAction` resolves role, hashes password, and creates row in `users`.
  5. `User::boot()` assigns prefixed UID (e.g., `EPE-2026-XXXXXX`).
* **API Endpoint & DB Tables:** `POST /api/users` | `users`, `roles`.

#### UC-07: Update User Profile & Role
* **Actors:** Super Admin, Evacuation Admin (for Personnel only)
* **Description:** Modifies official user parameters, contact numbers, or role designations.
* **API Endpoint & DB Tables:** `PUT /api/users/{id}` | `users`, `roles`.

#### UC-08: Deactivate / Delete User Account
* **Actors:** Super Admin, Evacuation Admin (Personnel only)
* **Description:** Executes a soft-delete (`deleted_at`) or flips `is_active = 0` to immediately block login access.
* **API Endpoint & DB Tables:** `DELETE /api/users/{id}` | `users`.

#### UC-09: Assign / Transfer Personnel to Center Station & Audit History
* **Actors:** Super Admin, Evacuation Admin
* **Description:** Station an Evacuation Personnel operator at a specific physical shelter facility, execute staff transfers, or relieve assignments with full audit tracking.
* **Preconditions:** Caller is Super Admin or Evacuation Admin; target user exists and has role `evac_personnel`. Target evacuation center exists in `evacuation_centers`.
* **Postconditions:** 
  - Previous active assignment for user in `center_personnel_assignments` marked `status = 'transferred'` with `relieved_at = now()`.
  - New assignment record inserted in `center_personnel_assignments` with `status = 'active'`, `assigned_by`, `assigned_at = now()`, and `event_id` (if disaster event active).
  - High-speed cached pointer `users.assigned_center_id` synchronized in the same atomic database transaction.
  - In-app notification (`user_notifications`) and TextBee SMS alert dispatched to personnel's contact number.
* **Main Flow:**
  1. Admin opens User Management panel, selects personnel, and opens the Station Assignment selector.
  2. Admin selects the target Evacuation Center and submits.
  3. System prompts confirmation modal with target center name: *"Are you sure you want to assign [Personnel Name] to [Evacuation Center Name]?"*.
  4. Upon confirmation, system executes atomic transaction:
     - Relieves active assignment (sets `relieved_at = now()`, `status = 'transferred'`).
     - Inserts new active record into `center_personnel_assignments`.
     - Updates cached pointer `users.assigned_center_id`.
  5. System generates in-app notification and sends SMS alert via TextBee.
  6. Frontend table and side-details drawer instantly update station badge.
* **Alternative Flows:**
  * Unassign / Relieve personnel → Sets active assignment in `center_personnel_assignments` to `status = 'completed'`, `relieved_at = now()`, sets `users.assigned_center_id = NULL`, and sends unassignment SMS.
  * Idempotent assignment (already stationed at selected center) → Returns no-op confirmation without creating duplicate rows.
  * Target center does not exist → HTTP 404 error returned.
* **API Endpoint & DB Tables:** `POST /api/users/{user}/assign-center` | `center_personnel_assignments`, `users`, `evacuation_centers`, `user_notifications`.

#### UC-09A: View Personnel Assignment History
* **Actors:** Super Admin, Evacuation Admin
* **Description:** Retrieves the complete historical and active station assignment audit log for a personnel user, showing assigned shelters, dates, relieving timestamps, assigned by admin names, and transfer remarks.
* **API Endpoint & DB Tables:** `GET /api/users/{user}/assignments` | `center_personnel_assignments`, `evacuation_centers`, `users`.

#### UC-09B: View Center Assigned Personnel Roster
* **Actors:** Super Admin, Evacuation Admin, Evacuation Personnel
* **Description:** Retrieves the active personnel roster and historical staff assignments for a given evacuation center facility.
* **API Endpoint & DB Tables:** `GET /api/evacuation-centers/{center}/personnel` | `center_personnel_assignments`, `users`, `roles`.

---

### Package 3: Evacuation Center & Room Configuration

#### UC-10: Create Evacuation Center
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Registers a new shelter facility with official name, capacity, GPS coordinates, and OpenStreetMap address.
* **API Endpoint & DB Tables:** `POST /api/evacuation-centers` | `evacuation_centers`.

#### UC-11: Configure Accommodation Units (Rooms / Spaces)
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Subdivides a center into discrete accommodation units (e.g. Room 101, Gym Sector A) with individual capacities.
* **API Endpoint & DB Tables:** `POST /api/centers/{centerId}/units` | `accommodation_units`, `accommodation_types`.

#### UC-12: Update Center Details & Operational Status
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Adjusts total shelter capacity, GPS tags, contact persons, or sets facility status (`active`, `full`, `inactive`).
* **API Endpoint & DB Tables:** `PUT /api/evacuation-centers/{center}` | `evacuation_centers`.

#### UC-13: Monitor Center Capacity & Occupancy Rates
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Real-time visualization of occupancy metrics, available headcounts, and percentage capacity utilized.
* **API Endpoint & DB Tables:** `GET /api/evacuation-centers/{center}/capacity` | `evacuation_centers`, `evacuation_records`.

#### UC-14: Auto-Update Shelter Capacity Count
* **Actors:** System Automated Trigger
* **Description:** Internal database transaction that increments `current_occupancy` during intake and decrements during checkout.
* **DB Tables:** `evacuation_centers.current_occupancy`.

#### UC-15: Assign Shelters to Disaster Umbrella
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Links multiple operational centers to an active disaster event (e.g., Typhoon Typhoon Carina).
* **API Endpoint & DB Tables:** `PATCH /api/events/{id}/assign-centers` | `evacuation_centers.current_event_id`.

---

### Package 4: Evacuation Operations, Intake & Tracking

#### UC-16: Verify Household via QR Code Scanner
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Personnel scans the household's official QR code via device camera. The system reads the encrypted household token, matches the local/SafeTrack database, and extracts family members for intake.
* **API Endpoint & DB Tables:** `POST /api/evacuations/process-scan` | `households`, `household_members`.

#### UC-17: Verify Household via Demographic Search (Fallback)
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Fallback when QR is damaged or unavailable. Personnel searches by household head surname or street.
* **API Endpoint & DB Tables:** `POST /api/evacuations/verify-manual` | `households`, `household_members`.

#### UC-18: Admit Household & Update Status to Evacuated
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Personnel checks off arriving members and confirms admission. Creates an admission record, sets household status to `Evacuated` (`household_status_id = 2`), and triggers automated center capacity increment.
* **API Endpoint & DB Tables:** `POST /api/evacuations/admit` | `evacuation_records`, `evacuated_members`, `evacuation_centers`.

#### UC-19: Allocate Accommodation Unit / Room (Direct & Household Drawer)
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Assigns, transfers, or releases living quarters (classroom, tent, designated space) for an admitted household. Can be executed either from the Accommodation Units management grid or directly inside the Evacuated Household Details side drawer.
* **Preconditions:** Household is verified and in `Evacuated` status (`household_status_id = 2`).
* **Postconditions:** `unit_allocations` record created or updated; unit occupancy count updated; evacuated household drawer updates assigned room badge.
* **Main Flow (Direct from Household Details Drawer):**
  1. Personnel opens Evacuated Households list and clicks a household row to open the side details drawer.
  2. Personnel navigates to the "Assigned Accommodation Unit" section and clicks "Assign Unit" or the unit dropdown.
  3. System lists available rooms with remaining bed/family capacity.
  4. Personnel selects target room/unit and confirms prompt: *"Are you sure you want to assign [Household Name] to [Unit Name]?"*.
  5. System allocates unit in `unit_allocations` and refreshes allocation status.
* **Alternative Flows:**
  * Room is at maximum capacity → Selection disabled with warning: *"Unit at full capacity"*.
  * Release / Unassign Unit → Removes unit allocation without checking out household.
* **API Endpoint & DB Tables:** `POST /api/centers/{centerId}/units`, `POST /api/units/{unitId}/allocations`, `DELETE /api/allocations/{id}` | `unit_allocations`, `accommodation_units`, `evacuation_records`.

#### UC-20: Monitor In-Center Stay Duration
* **Actors:** Evacuation Personnel, Evacuation Admin, Super Admin
* **Description:** Tracks length of stay, admission timestamps, and medical/dietary requirements for current shelter occupants.
* **API Endpoint & DB Tables:** `GET /api/evacuations` | `evacuation_records`.

#### UC-21: Update Member-Level Evacuation Status
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Updates status of individual family members (e.g. transferred to hospital, verified missing, returned home).
* **API Endpoint & DB Tables:** `PATCH /api/evacuations/{evacuationId}/members/{memberId}/status` | `evacuated_members`.

#### UC-22: Process Household Checkout & Release Unit
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Formally releases a family departing the shelter. Records checkout timestamp, frees up allocated room space, and decrements shelter occupancy.
* **API Endpoint & DB Tables:** `POST /api/evacuations/{evacuationId}/checkout` | `evacuation_records`, `unit_allocations`, `evacuation_centers`.

#### UC-23: View Live & Historical Evacuation Records
* **Actors:** Evacuation Personnel, Evacuation Admin, Super Admin
* **Description:** Browse and filter master logs of current evacuees and completed evacuations across centers.
* **API Endpoint & DB Tables:** `GET /api/evacuations` | `evacuation_records`.

---

### Package 5: SafeTrack Demographics & Privacy Isolation

#### UC-24: Sync Master Demographics from SafeTrack
* **Actors:** Super Admin, Evacuation Admin, System
* **Description:** Synchronizes verified household demographic baselines, street locations, and vulnerability flags from SafeTrack.
* **API Endpoint & DB Tables:** `POST /api/households/sync` | `households`, `household_members`, `addresses`.

#### UC-25: Restrict Demographic Data to Personnel Only
* **Actors:** System Security Boundary
* **Description:** Enforces architectural data isolation ensuring resident demographics are strictly consumed by authorized staff during intake and NEVER exposed to public portals or resident screens.
* **Policy Rule:** RBAC middleware filtering on all household demographic endpoints.

---

### Package 6: Evacuation Analytics & Demographic Intelligence

#### UC-26: Compute Real-Time Vulnerability Metrics
* **Actors:** System Automated Analytics Engine
* **Description:** Aggregates verified intake data to compute live demographic totals across active shelters.
* **API Endpoint & DB Tables:** `GET /api/analytics` | `analytics`, `evacuation_records`, `household_members`.

#### UC-27: View Age Cohorts (Children / Adults / Elderly)
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Inspects age breakdowns: Minors/Children (0–17), Productive Adults (18–59), and Senior Citizens (60+).

#### UC-28: View Pregnant & Lactating Women Count
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Visualizes total expectant mothers present across centers to prioritize maternal and pediatric aid.

#### UC-29: View PWD & Special Needs Count
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Visualizes evacuees tagged with physical or mental disabilities requiring accessible shelter amenities.

#### UC-30: View Gender Demographics
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Displays male-to-female ratios to evaluate sanitation and gender-separated quarters requirements.

#### UC-31: View Total Population Affected
* **Actors:** Evacuation Admin, Super Admin
* **Description:** High-level summary of total displaced households vs. total displaced individuals.

#### UC-32: Export Analytics & System-Wide Summaries
* **Actors:** Super Admin
* **Description:** Exports consolidated demographic intelligence into formal CSV/PDF reports for municipal DRRMO and council briefing.
* **API Endpoint & DB Tables:** `GET /api/analytics/export` | `analytics`, `evacuation_centers`.

---

### Package 7: Center Incident & Condition Reporting

#### UC-33: Report Facility / Medical / Safety Incident
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** On-site personnel logs an immediate facility issue (water outage, broken latrine), security threat, or medical emergency.
* **Input Payload:** `evacuation_center_id`, `category` (incident, facility_issue, health_issue, safety_issue, other), `title`, `description`, `severity` (low, medium, high, critical).
* **API Endpoint & DB Tables:** `POST /api/center-issue-reports` | `center_issue_reports`, `center_issue_categories`, `severity_levels`.

#### UC-34: Upload Photo / Evidence Attachment
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Attaches photographic evidence (`image/jpeg, png, max 5MB`) to validate structural damage or health hazards.
* **API Endpoint & DB Tables:** Handled within `POST /api/center-issue-reports` (multipart/form-data) | `center_issue_reports.attachment_path`.

#### UC-35: Monitor Shelter Health via Issues Kanban
* **Actors:** Evacuation Personnel, Evacuation Admin, Super Admin
* **Description:** Visualizes facility problems across an interactive Kanban board (`Open`, `In Progress`, `Resolved`, `Closed`).
* **API Endpoint & DB Tables:** `GET /api/center-issue-reports` | `center_issue_reports`.

#### UC-36: Triage & Transition Issue Status & Severity
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Admin assigns personnel, adjusts severity, or moves report cards between workflow lanes as repairs proceed.
* **API Endpoint & DB Tables:** `PATCH /api/center-issue-reports/{id}/status` | `center_issue_reports`.

#### UC-37: Resolve or Delete Incident Report
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Archives or removes false/resolved incident records once center integrity is restored.
* **API Endpoint & DB Tables:** `DELETE /api/center-issue-reports/{id}` | `center_issue_reports`.

---

### Package 8: Resource Requests & Response Routing

#### UC-38: File In-Center Resource Supply Request
* **Actors:** Evacuation Personnel, Evacuation Admin
* **Description:** Logs urgent resource deficits for drinking water, hygiene kits, food packs, or response manpower.
* **Input Payload:** `evacuation_center_id`, `resource_type`, `quantity`, `description`.
* **API Endpoint & DB Tables:** `POST /api/resource-requests` | `resource_requests`, `resource_request_statuses`.

#### UC-39: Monitor Requests via Kanban / List Board
* **Actors:** Evacuation Personnel, Evacuation Admin, Super Admin
* **Description:** Interactive status tracking (`pending` → `acknowledged` → `approved` / `rejected` → `delivered`).
* **API Endpoint & DB Tables:** `GET /api/resource-requests` | `resource_requests`.

#### UC-40: Update Logistics Request Status
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Admin reviews demand, adjusts approved quantities, and advances dispatch milestones.
* **API Endpoint & DB Tables:** `PATCH /api/resource-requests/{id}/status` | `resource_requests`.

#### UC-41: Route Supply / Personnel Aid to ResQperation
* **Actors:** System Automated Gateway / Evac Admin
* **Description:** Forwards critical resource or search-and-rescue demands directly to the ResQperation incident command system.
* **External Integration:** ResQperation REST API bridge.

---

### Package 9: SMS & Mobile Push Alerts (Admin Exclusive)

> **Access Control Rule:** Evacuation Personnel are strictly barred from creating or transmitting municipal SMS and Push alerts to prevent decentralized panic, contradictory evacuation directives, and uncoordinated telco gateway expenses. All public warning broadcasts are restricted to Evacuation Admins and Super Admins.

#### UC-42: Broadcast SMS Alerts (Offline Citizens)
* **Actors:** Evacuation Admin, Super Admin *(Evacuation Personnel Restricted)*
* **Description:** Dispatches critical disaster advisories and evacuation orders over GSM cellular networks directly to resident phones.
* **API Endpoint & DB Tables:** `POST /api/notifications` | `notifications`, `notification_logs`.

#### UC-43: Dispatch Mobile Push Alerts (ResQperation)
* **Actors:** Evacuation Admin, Super Admin *(Evacuation Personnel Restricted)*
* **Description:** Sends high-priority push notifications with sound alerts to citizens using the ResQperation mobile app.
* **API Endpoint & DB Tables:** `POST /api/notifications` | `notifications`, `notification_logs`.

#### UC-44: Configure Recurring Broadcast Dispatches
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Automates repeating announcements (e.g. hourly weather updates or daily situationers) with designated intervals (`hourly`, `daily`, `weekly`) and cutoff dates.
* **Input Payload:** `is_recurring = true`, `recurrence_type` (`hourly`, `daily`, `weekly`), `recurrence_end_at`.
* **API Endpoint & DB Tables:** `POST /api/notifications` | `notifications`.

#### UC-45: Filter Target Audience & Recipient Scopes
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Selects explicit delivery scopes (`not_evacuated` for pre-disaster evacuation calls, `all` for public broadcasts, or specific evacuation centers for in-shelter notices) to prevent alert fatigue and optimize SMS delivery.
* **API Endpoint & DB Tables:** `POST /api/notifications` | `notifications`, `households`.

#### UC-46: Auto-Draft Center Status & Redirection Advisory (EVATRACK ADVISORY)
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Computes real-time occupancy and capacity rates across all operational evacuation centers, automatically auto-drafting an official "EVATRACK ADVISORY" that flags near-capacity shelters (≥80% full) to avoid and recommends open shelters with space available for optimal citizen crowd redirection.
* **API Endpoint & DB Tables:** `GET /api/evacuation-centers`, `POST /api/notifications` | `evacuation_centers`, `evacuation_records`, `notifications`.

#### UC-47: Monitor Dispatch Logs & Delivery Success Rates
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Inspects individual recipient delivery statuses (`sent`, `failed`, `pending`) and computes delivery success percentages.
* **API Endpoint & DB Tables:** `GET /api/notifications/{notification}/logs` | `notification_logs`.

#### UC-48: Cancel / Abort Scheduled Broadcast
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Immediately terminates recurring dispatches or cancels scheduled alerts prior to transmission.
* **API Endpoint & DB Tables:** `DELETE /api/notifications/{notification}` | `notifications`.

---

### Package 10: Disaster Event Coordination

#### UC-49: Declare Disaster Event
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Initializes a new emergency umbrella (typhoon, flood, landslide, earthquake) with disaster type, severity, and operational jurisdiction.
* **API Endpoint & DB Tables:** `POST /api/events` | `disaster_events`, `disaster_types`.

#### UC-50: Track Active & Historical Disaster Scenarios
* **Actors:** Evacuation Personnel, Evacuation Admin, Super Admin
* **Description:** Monitors live operational events and reviews completed past disaster operations.
* **API Endpoint & DB Tables:** `GET /api/events/active`, `GET /api/events/history` | `disaster_events`.

#### UC-51: Terminate Disaster Event
* **Actors:** Evacuation Admin, Super Admin
* **Description:** Formally concludes the disaster operational phase, setting `ended_at` and archiving associated center deployments.
* **API Endpoint & DB Tables:** `PATCH /api/events/{id}/end` | `disaster_events`.

---

### Package 11: Emergency Intervention & Oversight

#### UC-52: Emergency Override of Evac Admin Decisions
* **Actors:** Super Admin Only
* **Description:** Protocol-level override power to countermand center closures, re-allocate critical assets, or modify administrative settings during catastrophic contingencies.

#### UC-53: Emergency Override of Personnel Actions
* **Actors:** Super Admin Only
* **Description:** Direct administrative override to correct intake data, adjust erroneous allocations, or remediate shelter discrepancies.

#### UC-54: Global System Oversight & Audit Inspection
* **Actors:** Super Admin Only
* **Description:** Continuous monitoring across all system metrics, user actions, and error logs for accountability and governance compliance.
* **API Endpoint & DB Tables:** `GET /api/reports/system-wide` | System-wide audit inspection.

---

## 5. Role-to-Use-Case Traceability Matrix

| Use Case ID & Title | Super Admin | Evac Admin | Evac Personnel | Households | SafeTrack | ResQperation |
|:---|:---:|:---:|:---:|:---:|:---:|:---:|
| **UC-01 Login with Credentials** | ✓ | ✓ | ✓ | — | — | — |
| **UC-02 Logout & Revoke Session** | ✓ | ✓ | ✓ | — | — | — |
| **UC-03 View & Update Profile** | ✓ | ✓ | ✓ | — | — | — |
| **UC-04 Change Password** | ✓ | ✓ | ✓ | — | — | — |
| **UC-05 List System Users** | ✓ | ✓ | — | — | — | — |
| **UC-06 Register User Account** | **✓ (Exclusive)** | — | — | — | — | — |
| **UC-07 Update User Profile & Role** | ✓ | ✓ (Personnel) | — | — | — | — |
| **UC-08 Deactivate / Soft-Delete User** | ✓ | ✓ (Personnel) | — | — | — | — |
| **UC-09 Assign Personnel to Center Station** | ✓ | ✓ | — | — | — | — |
| **UC-10 Create Evacuation Center** | ✓ | ✓ | — | — | — | — |
| **UC-11 Configure Accommodation Units** | ✓ | ✓ | — | — | — | — |
| **UC-12 Update Center Details & Status** | ✓ | ✓ | — | — | — | — |
| **UC-13 Monitor Center Capacity Rates** | ✓ | ✓ | ✓ | — | — | — |
| **UC-14 Auto-Update Shelter Capacity** | *System* | *System* | *System* | — | — | — |
| **UC-15 Assign Shelters to Event** | ✓ | ✓ | — | — | — | — |
| **UC-16 Verify via QR Scanner** | ✓ | ✓ | ✓ | Presents QR | Syncs data | — |
| **UC-17 Verify via Demographic Search** | ✓ | ✓ | ✓ | Presents ID | Syncs data | — |
| **UC-18 Admit Household & Set Evacuated** | ✓ | ✓ | ✓ | Admitted | — | — |
| **UC-19 Allocate Accommodation Unit** | ✓ | ✓ | ✓ | Allocated | — | — |
| **UC-20 Monitor In-Center Stay Duration** | ✓ | ✓ | ✓ | — | — | — |
| **UC-21 Update Member-Level Status** | ✓ | ✓ | ✓ | — | — | — |
| **UC-22 Process Checkout & Free Space** | ✓ | ✓ | ✓ | Released | — | — |
| **UC-23 View Evacuation Records** | ✓ | ✓ | ✓ | — | — | — |
| **UC-24 Sync Master Demographics** | *System* | ✓ | — | — | **Source** | — |
| **UC-25 Restrict Demographic Data** | *Policy* | *Policy* | *Policy* | Hidden | Isolated | — |
| **UC-26 Compute Vulnerability Metrics** | *System* | *System* | *System* | — | — | — |
| **UC-27 View Age Cohorts Breakdown** | ✓ | ✓ | — | — | — | — |
| **UC-28 View Pregnant Women Count** | ✓ | ✓ | — | — | — | — |
| **UC-29 View PWD / Special Needs Count**| ✓ | ✓ | — | — | — | — |
| **UC-30 View Gender Demographics** | ✓ | ✓ | — | — | — | — |
| **UC-31 View Total Affected Population** | ✓ | ✓ | — | — | — | — |
| **UC-32 Export System-Wide Summaries** | **✓ (Exclusive)** | — | — | — | — | — |
| **UC-33 Report Incident / Center Issue** | ✓ | ✓ | ✓ | — | — | — |
| **UC-34 Upload Evidence Photo** | ✓ | ✓ | ✓ | — | — | — |
| **UC-35 Monitor Issues Kanban Board** | ✓ | ✓ | ✓ | — | — | — |
| **UC-36 Triage & Update Issue Status** | ✓ | ✓ | — | — | — | — |
| **UC-37 Resolve or Delete Incident** | ✓ | ✓ | — | — | — | — |
| **UC-38 File Resource Supply Request** | ✓ | ✓ | ✓ | — | — | — |
| **UC-39 Monitor Resource Demands Board**| ✓ | ✓ | ✓ | — | — | — |
| **UC-40 Update Resource Request Status** | ✓ | ✓ | — | — | — | — |
| **UC-41 Route Aid to ResQperation** | *System* | ✓ | — | — | — | **Target** |
| **UC-42 Broadcast Emergency SMS** | ✓ | ✓ | — | **Receives** | — | — |
| **UC-43 Dispatch Mobile Push Alerts** | ✓ | ✓ | — | **Receives** | — | **Gateway** |
| **UC-44 Configure Recurring Dispatches** | ✓ | ✓ | — | **Receives** | — | **Gateway** |
| **UC-45 Filter Target Audience** | ✓ | ✓ | — | — | — | — |
| **UC-46 Auto-Draft EVATRACK ADVISORY** | ✓ | ✓ | — | **Receives** | — | — |
| **UC-47 Monitor Dispatch Logs & Rates** | ✓ | ✓ | — | — | — | — |
| **UC-48 Cancel / Abort Scheduled Alert** | ✓ | ✓ | — | — | — | — |
| **UC-49 Declare Disaster Event** | ✓ | ✓ | — | — | — | — |
| **UC-50 Track Active & Historical Events**| ✓ | ✓ | ✓ | — | — | — |
| **UC-51 Terminate Disaster Event** | ✓ | ✓ | — | — | — | — |
| **UC-52 Emergency Override of Admins** | **✓ (Exclusive)** | — | — | — | — | — |
| **UC-53 Emergency Override of Personnel**| **✓ (Exclusive)** | — | — | — | — | — |
| **UC-54 Global System Oversight** | **✓ (Exclusive)** | — | — | — | — | — |
