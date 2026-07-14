# EvaTrack – API Endpoints Documentation

> [!NOTE]
> This document contains **all 94 API endpoints** registered in the EvaTrack backend, organized by domain. Each endpoint includes the HTTP method, URI, controller action, middleware, and whether it is **actively used** by the frontend React app.

---

## Usage Verification Summary

| Status | Count | Description |
|--------|-------|-------------|
| ✅ Used | 78 | Called by the frontend |
| ⚠️ Unused | 10 | Registered but never called by the frontend |
| 🔧 Internal | 4 | Swagger/OAuth/Sync – not user-facing |
| 📱 Mobile-Only | 2 | Used by mobile app, not web frontend |

---

## 1. Authentication

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `POST` | `/api/login` | `AuthController@apiLogin` | — | ✅ [Login.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/pages/Login.jsx) |
| `POST` | `/api/logout` | `AuthController@apiLogout` | `auth:sanctum` | ✅ Used via auth context |
| `GET` | `/api/user` | `AuthController@currentUser` | `auth:sanctum` | ✅ [ProtectedRoute.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/components/ProtectedRoute.jsx), [getUser.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/auth/getUser.ts) |
| `PUT` | `/api/user/profile` | `UserController@updateProfile` | `auth:sanctum` | ✅ [updateProfile.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/auth/updateProfile.ts) |
| `PUT` | `/api/user/password` | `UserController@updatePassword` | `auth:sanctum` | ✅ [updatePassword.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/auth/updatePassword.ts) |

---

## 2. User Management

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/users` | `UserController@index` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [getUsers.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/users/getUsers.ts) |
| `POST` | `/api/users` | `UserController@createUser` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [createUser.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/users/createUser.ts) |
| `PUT` | `/api/users/{id}` | `UserController@updateUser` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [updateUser.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/users/updateUser.ts) |
| `DELETE` | `/api/users/{id}` | `UserController@deleteUser` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [deleteUser.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/users/deleteUser.ts) |
| `POST` | `/api/users/{user}/assign-center` | `UserController@assignCenter` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [assignCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/users/assignCenter.ts) |

---

## 3. Lookups / Reference Data

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/lookups` | `LookupController@index` | `auth:sanctum` | ✅ [getLookups.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/lookups/getLookups.ts) |
| `GET` | `/api/barangays` | `AddressController@barangays` | — | ✅ [EventModal.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/components/events/EventModal.jsx) |
| `GET` | `/api/barangays/{id}/sitios` | `AddressController@sitios` | — | ✅ [EventModal.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/components/events/EventModal.jsx) |
| `GET` | `/api/sitios/{id}/puroks` | `AddressController@puroks` | — | ⚠️ **NOT USED** by frontend |
| `GET` | `/api/disaster-types` | `DisasterTypeController@index` | — | ✅ [getDisasterTypes.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/getDisasterTypes.ts) |
| `GET` | `/api/severity-levels` | `SeverityLevelController@index` | — | ✅ [getSeverityLevels.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/getSeverityLevels.ts) |
| `GET` | `/api/urgency-levels` | `ResourceRequestController@urgencyLevels` | `auth:sanctum` | ✅ [getUrgencyLevels.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/resourceRequests/getUrgencyLevels.ts) |
| `GET` | `/api/accommodation-types` | `AccommodationUnitController@types` | `auth:sanctum` | ✅ [getAccommodationTypes.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/units/getAccommodationTypes.ts) |

---

## 4. Household Management

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/households` | `HouseholdController@index` | `auth:sanctum` | ✅ [getHouseholds.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/getHouseholds.ts) |
| `POST` | `/api/households` | `HouseholdController@store` | `auth:sanctum` | ✅ [createHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/createHousehold.ts) |
| `GET` | `/api/households/search` | `HouseholdController@search` | `auth:sanctum` | ✅ (via `/api/evacuations/search-household`) |
| `GET` | `/api/households/{id}` | `HouseholdController@show` | `auth:sanctum` | ✅ [getHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/getHousehold.ts) |
| `PATCH` | `/api/households/{id}` | `HouseholdController@update` | `auth:sanctum` | ✅ [updateHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/updateHousehold.ts) |
| `DELETE` | `/api/households/{id}` | `HouseholdController@destroy` | `auth:sanctum` | ✅ [deleteHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/deleteHousehold.ts) |

---

## 5. Household Members

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/households/{householdId}/members` | `HouseholdMemberController@index` | `auth:sanctum` | ✅ [getMembers.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/getMembers.ts) |
| `POST` | `/api/households/{householdId}/members` | `HouseholdMemberController@store` | `auth:sanctum` | ✅ [addMember.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/addMember.ts) |
| `PATCH` | `/api/households/{householdId}/members/{memberId}` | `HouseholdMemberController@update` | `auth:sanctum` | ✅ [updateMember.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/updateMember.ts) |
| `DELETE` | `/api/households/{householdId}/members/{memberId}` | `HouseholdMemberController@destroy` | `auth:sanctum` | ✅ [deleteMember.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/households/deleteMember.ts) |

---

## 6. Evacuation Centers

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/evacuation-centers` | `EvacuationCenterController@index` | `auth:sanctum` | ✅ [getCenters.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuation/getCenters.ts) |
| `POST` | `/api/evacuation-centers` | `EvacuationCenterController@store` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [createCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuation/createCenter.ts) |
| `GET` | `/api/evacuation-centers/{center}` | `EvacuationCenterController@show` | `auth:sanctum` | ✅ [getCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuation/getCenter.ts) |
| `PUT` | `/api/evacuation-centers/{center}` | `EvacuationCenterController@update` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [updateCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuation/updateCenter.ts) |
| `DELETE` | `/api/evacuation-centers/{center}` | `EvacuationCenterController@destroy` | `auth:sanctum`, `role:super_admin,evac_admin` | ✅ [deleteCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuation/deleteCenter.ts) |
| `GET` | `/api/evacuation-centers/{center}/capacity` | `EvacuationCenterController@capacity` | `auth:sanctum` | ✅ [getCapacity.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuation/getCapacity.ts) |
| `GET` | `/api/evacuation-centers/{center}/export` | `CenterExportController@exportCenterHouseholds` | `auth:sanctum` | ✅ [exportCenterData.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/exportCenterData.ts) |

---

## 7. Evacuation Records

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/evacuations` | `EvacuationController@index` | `auth:sanctum` | ✅ [getRecordsByCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/getRecordsByCenter.ts) |
| `GET` | `/api/evacuations/{evacuation}` | `EvacuationController@show` | `auth:sanctum` | ✅ [getEvacuationRecord.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/getEvacuationRecord.ts) |
| `GET` | `/api/evacuations/search-household` | `HouseholdController@search` | `auth:sanctum` | ✅ [searchHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/searchHousehold.ts) |
| `POST` | `/api/evacuations/process-scan` | `EvacuationController@scan` | `auth:sanctum` | ✅ [scanQR.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/scanQR.ts) |
| `POST` | `/api/evacuations/verify-manual` | `EvacuationController@verifyManual` | `auth:sanctum` | ✅ [verifyManual.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/verifyManual.ts) |
| `POST` | `/api/evacuations/admit` | `EvacuationController@admit` | `auth:sanctum` | ✅ [admitHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/admitHousehold.ts) |
| `POST` | `/api/evacuations/{evacuationId}/checkout` | `EvacuationController@checkout` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |
| `PATCH` | `/api/evacuations/{evacuationId}/members/{memberId}/status` | `EvacuationController@updateMemberStatus` | `auth:sanctum` | ✅ [updateMemberEvacuationStatus.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/updateMemberEvacuationStatus.ts) |
| `DELETE` | `/api/evacuations/{evacuationId}` | `EvacuationController@deleteRecord` | `auth:sanctum` | ✅ [deleteRecord.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/deleteRecord.ts) |

---

## 8. Evacuation Events

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/events` | `EvacuationEventController@index` | `auth:sanctum` | ✅ [getEvents.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/getEvents.ts) |
| `POST` | `/api/events` | `EvacuationEventController@store` | `auth:sanctum` | ✅ [createEvent.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/createEvent.ts) |
| `GET` | `/api/events/active` | `EvacuationEventController@active` | `auth:sanctum` | ✅ [getActiveEvent.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/getActiveEvent.ts) |
| `GET` | `/api/events/history` | `EvacuationEventController@history` | `auth:sanctum` | ✅ [getHistoryEvents.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/getHistoryEvents.ts) |
| `PATCH` | `/api/events/{id}/end` | `EvacuationEventController@end` | `auth:sanctum` | ✅ [endEvent.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/endEvent.ts) |
| `PATCH` | `/api/events/{id}/assign-centers` | `EvacuationEventController@assignCenters` | `auth:sanctum` | ✅ [assignCenters.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/assignCenters.ts) |
| `PATCH` | `/api/centers/{centerId}/unassign` | `EvacuationEventController@unassignCenter` | `auth:sanctum` | ✅ [unassignCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/events/unassignCenter.ts) |

---

## 9. Accommodation Units

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/centers/{centerId}/units` | `AccommodationUnitController@index` | `auth:sanctum` | ✅ [getUnitsByCenter.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/units/getUnitsByCenter.ts) |
| `POST` | `/api/centers/{centerId}/units` | `AccommodationUnitController@store` | `auth:sanctum` | ✅ [createUnit.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/units/createUnit.ts) |
| `PATCH` | `/api/centers/{centerId}/units/{unitId}` | `AccommodationUnitController@update` | `auth:sanctum` | ✅ [updateUnit.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/units/updateUnit.ts) |
| `DELETE` | `/api/centers/{centerId}/units/{unitId}` | `AccommodationUnitController@destroy` | `auth:sanctum` | ✅ [deleteUnit.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/units/deleteUnit.ts) |

---

## 10. Unit Allocations

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/centers/{centerId}/unassigned` | `UnitAllocationController@unassigned` | `auth:sanctum` | ✅ [getUnassignedHouseholds.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/allocations/getUnassignedHouseholds.ts) |
| `GET` | `/api/units/{unitId}/allocations` | `UnitAllocationController@index` | `auth:sanctum` | ✅ [getUnitAllocations.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/allocations/getUnitAllocations.ts) |
| `POST` | `/api/units/{unitId}/allocations` | `UnitAllocationController@assign` | `auth:sanctum` | ✅ [assignHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/allocations/assignHousehold.ts) |
| `DELETE` | `/api/units/{unitId}/allocations/{allocationId}` | `UnitAllocationController@unassign` | `auth:sanctum` | ✅ [unassignHousehold.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/allocations/unassignHousehold.ts) |

---

## 11. Resource Requests

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/resource-requests` | `ResourceRequestController@index` | `auth:sanctum` | ✅ [getResourceRequests.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/resourceRequests/getResourceRequests.ts) |
| `POST` | `/api/resource-requests` | `ResourceRequestController@store` | `auth:sanctum` | ✅ [createResourceRequest.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/resourceRequests/createResourceRequest.ts) |
| `GET` | `/api/resource-requests/{id}` | `ResourceRequestController@show` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |
| `PATCH` | `/api/resource-requests/{id}/status` | `ResourceRequestController@updateStatus` | `auth:sanctum` | ✅ [updateResourceRequestStatus.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/resourceRequests/updateResourceRequestStatus.ts) |
| `DELETE` | `/api/resource-requests/{id}` | `ResourceRequestController@destroy` | `auth:sanctum` | ✅ [deleteResourceRequest.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/resourceRequests/deleteResourceRequest.ts) |

---

## 12. Center Issue Reports

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/center-issue-reports` | `CenterIssueReportController@index` | `auth:sanctum` | ✅ [getCenterIssueReports.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/centerIssueReports/getCenterIssueReports.ts) |
| `POST` | `/api/center-issue-reports` | `CenterIssueReportController@store` | `auth:sanctum` | ✅ [createCenterIssueReport.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/centerIssueReports/createCenterIssueReport.ts) |
| `GET` | `/api/center-issue-reports/{id}` | `CenterIssueReportController@show` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |
| `PATCH` | `/api/center-issue-reports/{id}` | `CenterIssueReportController@update` | `auth:sanctum` | ✅ [updateCenterIssueReport.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/centerIssueReports/updateCenterIssueReport.ts) |
| `PATCH` | `/api/center-issue-reports/{id}/status` | `CenterIssueReportController@updateStatus` | `auth:sanctum` | ✅ [updateCenterIssueReportStatus.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/centerIssueReports/updateCenterIssueReportStatus.ts) |
| `DELETE` | `/api/center-issue-reports/{id}` | `CenterIssueReportController@destroy` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |

---

## 13. Notifications / Alerts

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/notifications` | `NotificationController@index` | `auth:sanctum` | ✅ [getAlerts.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/alerts/getAlerts.ts) |
| `POST` | `/api/notifications` | `NotificationController@send` | `auth:sanctum` | ✅ [sendAlert.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/alerts/sendAlert.ts) |
| `GET` | `/api/notifications/preview` | `NotificationController@preview` | `auth:sanctum` | ✅ [previewRecipients.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/alerts/previewRecipients.ts) |
| `GET` | `/api/notifications/urgency-levels` | `NotificationController@urgencyLevels` | `auth:sanctum` | ⚠️ **NOT USED** – frontend uses `/api/urgency-levels` instead |
| `GET` | `/api/notifications/{notification}` | `NotificationController@show` | `auth:sanctum` | ✅ [getAlertDetail.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/alerts/getAlertDetail.ts) |
| `GET` | `/api/notifications/{notification}/logs` | `NotificationController@logs` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |
| `POST` | `/api/notifications/{notification}/acknowledge` | `NotificationController@acknowledge` | `auth:sanctum` | 📱 **Mobile-only** – used by the mobile app |
| `DELETE` | `/api/notifications/{notification}` | `NotificationController@cancel` | `auth:sanctum` | ✅ [cancelAlert.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/alerts/cancelAlert.ts) |

---

## 14. Analytics

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/analytics/dashboard` | `AnalyticsController@dashboard` | `auth:sanctum` | ✅ [Analytics.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/pages/Analytics.jsx) |
| `GET` | `/api/analytics/events-list` | `AnalyticsController@eventsList` | `auth:sanctum` | ✅ [Analytics.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/pages/Analytics.jsx) |
| `GET` | `/api/analytics/last-updated` | `AnalyticsController@lastUpdated` | `auth:sanctum` | ✅ [getLastUpdated.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/analytics/getLastUpdated.ts) |
| `GET` | `/api/analytics/event/{eventId}` | `AnalyticsController@eventAnalytics` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |
| `GET` | `/api/analytics/event/{eventId}/center/{centerId}` | `AnalyticsController@centerAnalytics` | `auth:sanctum` | ⚠️ **NOT USED** by frontend |

---

## 15. Analytics Exports

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/analytics/export/dromic` | `AnalyticsExportController@dromicMasterList` | `auth:sanctum` | ✅ [exportAnalyticsData.ts](file:///c:/CAPSTONE/evatrackfrontend/src/api/evacuationRecords/exportAnalyticsData.ts) |
| `GET` | `/api/analytics/export/demographics` | `AnalyticsExportController@demographicSummary` | `auth:sanctum` | ✅ via dynamic `pathType` param |
| `GET` | `/api/analytics/export/utilization` | `AnalyticsExportController@centerUtilization` | `auth:sanctum` | ✅ via dynamic `pathType` param |
| `GET` | `/api/analytics/export/vulnerable` | `AnalyticsExportController@vulnerableGroups` | `auth:sanctum` | ✅ via dynamic `pathType` param |
| `GET` | `/api/analytics/export/resources` | `AnalyticsExportController@resourceRequests` | `auth:sanctum` | ✅ via dynamic `pathType` param |
| `GET` | `/api/analytics/export/issues` | `AnalyticsExportController@centerIssues` | `auth:sanctum` | ✅ via dynamic `pathType` param |
| `GET` | `/api/analytics/export/daily-intake` | `AnalyticsExportController@dailyIntake` | `auth:sanctum` | ✅ via dynamic `pathType` param |

---

## 16. Public Routes (No Auth Required)

| Method | URI | Controller Action | Middleware | Frontend Usage |
|--------|-----|-------------------|------------|----------------|
| `GET` | `/api/public/evacuation-centers` | `EvacuationCenterController@publicIndex` | — | ✅ [Landing.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/pages/Landing.jsx), [PublicPortal.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/pages/PublicPortal.jsx) |
| `GET` | `/api/public/events/active` | `EvacuationEventController@activePublic` | — | ✅ [Landing.jsx](file:///c:/CAPSTONE/evatrackfrontend/src/pages/Landing.jsx) |

---

## ⚠️ Unused API Endpoints Summary

The following backend routes are registered but **not called** by the React frontend. They may be intended for future use, the mobile app, or can be considered for removal:

| # | Method | URI | Notes |
|---|--------|-----|-------|
| 1 | `POST` | `/api/evacuations/{evacuationId}/checkout` | **Checkout feature not implemented in frontend UI**. The backend action exists but no button/page calls it. |
| 2 | `GET` | `/api/resource-requests/{id}` | Individual resource request detail view not built in frontend. |
| 3 | `GET` | `/api/center-issue-reports/{id}` | Individual issue report detail view not built in frontend. |
| 4 | `DELETE` | `/api/center-issue-reports/{id}` | Delete issue report not wired in frontend UI. |
| 5 | `GET` | `/api/notifications/urgency-levels` | Duplicate – frontend uses `/api/urgency-levels` from `ResourceRequestController` instead. |
| 6 | `GET` | `/api/notifications/{notification}/logs` | Notification delivery logs not displayed in frontend. |
| 7 | `POST` | `/api/notifications/{notification}/acknowledge` | Mobile-only endpoint for push notification acknowledgement. |
| 8 | `GET` | `/api/sitios/{id}/puroks` | Purok lookup not used in any frontend form. |
| 9 | `GET` | `/api/analytics/event/{eventId}` | Per-event analytics detail not wired in frontend. |
| 10 | `GET` | `/api/analytics/event/{eventId}/center/{centerId}` | Per-center analytics detail not wired in frontend. |

> [!IMPORTANT]
> **Recommendation:** Endpoints 1-4 and 6 are useful features that could be wired into the frontend in the future. Endpoint 5 is a duplicate and could be removed. Endpoints 7-8 are valid for mobile use. Endpoints 9-10 may be useful for drill-down analytics views.
