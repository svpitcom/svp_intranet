# Endpoint inventory

Generated from app/Config/routes.php. Middleware is only the first layer; controller/record/department checks also apply.

| Method | Path | Handler | Middleware |
|---|---|---|---|
| GET | `/login` | AuthController::showLogin |  |
| POST | `/login` | AuthController::login |  |
| GET | `/it-backup` | BackupChecklistController::index | auth |
| GET | `/it-backup/create` | BackupChecklistController::edit | auth |
| POST | `/it-backup/create` | BackupChecklistController::save | auth |
| GET | `/it-backup/{id}` | BackupChecklistController::edit | auth |
| POST | `/it-backup/{id}` | BackupChecklistController::save | auth |
| GET | `/it-backup/{id}/export` | BackupChecklistController::export | auth |
| GET | `/kpi/forms` | KpiFormController::index | auth |
| GET | `/kpi/forms/create` | KpiFormController::edit | auth |
| POST | `/kpi/forms/create` | KpiFormController::save | auth |
| GET | `/kpi/forms/{id}` | KpiFormController::edit | auth |
| POST | `/kpi/forms/{id}` | KpiFormController::save | auth |
| GET | `/kpi/forms/{id}/export` | KpiFormController::export | auth |
| POST | `/kpi/forms/{id}/sharepoint` | KpiFormController::sendSharePoint | auth, role:admin |
| GET | `/kpi` | KpiController::index | auth |
| GET | `/kpi/create` | KpiController::create | auth |
| POST | `/kpi/create` | KpiController::store | auth |
| POST | `/kpi/{id}/result` | KpiController::result | auth |
| GET | `/kpi/{id}/history` | KpiController::history | auth |
| GET | `/tickets` | WorkController::tickets | auth |
| GET | `/tickets/create` | WorkController::newTicket | auth |
| POST | `/tickets/create` | WorkController::createTicket | auth |
| GET | `/requests` | WorkController::requests | auth |
| GET | `/requests/create` | WorkController::newRequest | auth |
| POST | `/requests/create` | WorkController::createRequest | auth |
| GET | `/work/{id}` | WorkController::show | auth |
| POST | `/work/{id}/action` | WorkController::action | auth |
| GET | `/work/{id}/attachment` | WorkController::attachment | auth |
| GET | `/announcements` | AnnouncementController::index | auth |
| GET | `/announcements/create` | AnnouncementController::create | auth, role:admin,manager |
| POST | `/announcements/create` | AnnouncementController::store | auth, role:admin,manager |
| GET | `/announcements/{id}` | AnnouncementController::show | auth |
| GET | `/announcements/{id}/edit` | AnnouncementController::edit | auth, role:admin,manager |
| POST | `/announcements/{id}/edit` | AnnouncementController::update | auth, role:admin,manager |
| POST | `/announcements/{id}/read` | AnnouncementController::acknowledge | auth |
| GET | `/notifications` | NotificationController::index | auth |
| POST | `/notifications/read` | NotificationController::read | auth |
| GET | `/document-control` | DocumentControlController::index | auth, documentControl |
| GET | `/document-control/files` | DocumentControlController::files | auth, documentControl |
| GET | `/document-control/create` | DocumentControlController::create | auth, documentControl |
| POST | `/document-control/create` | DocumentControlController::store | auth, documentControl |
| GET | `/document-control/{id}/edit` | DocumentControlController::edit | auth, documentControl |
| POST | `/document-control/{id}/edit` | DocumentControlController::update | auth, documentControl |
| POST | `/devices/import-excel` | SharePointController::importDevices | auth, role:admin |
| POST | `/devices/sync-excel` | SharePointController::syncDevices | auth, role:admin |
| POST | `/users/sync-excel` | SharePointController::syncUsers | auth, role:admin |
| POST | `/users/import-excel` | SharePointController::importUsers | auth, role:admin |
| POST | `/sharepoint/export-tables` | SharePointController::exportTables | auth, role:admin |
| POST | `/pm-records/{id}/sharepoint` | PmRecordController::retryPdf | auth, role:admin |
| GET | `/sharepoint` | SharePointController::index | auth, role:admin |
| POST | `/sharepoint/export` | SharePointController::export | auth, role:admin |
| GET | `/pm-records/{id}/attachment` | FileController::record | auth |
| GET | `/pm-schedules/{id}/attachment` | FileController::schedule | auth |
| GET | `/` | DashboardController::index | auth |
| GET | `/users` | UserController::index | auth |
| GET | `/users/create` | UserController::create | auth, role:admin |
| POST | `/users/create` | UserController::store | auth, role:admin |
| GET | `/users/{id}/edit` | UserController::edit | auth, role:admin |
| POST | `/users/{id}/edit` | UserController::update | auth, role:admin |
| POST | `/users/{id}/delete` | UserController::destroy | auth, role:admin |
| GET | `/users/search` | UserController::search | auth |
| GET | `/departments` | DepartmentController::index | auth |
| GET | `/departments/create` | DepartmentController::create | auth, role:admin |
| POST | `/departments/create` | DepartmentController::store | auth, role:admin |
| GET | `/departments/{id}/edit` | DepartmentController::edit | auth, role:admin |
| POST | `/departments/{id}/edit` | DepartmentController::update | auth, role:admin |
| POST | `/departments/{id}/delete` | DepartmentController::destroy | auth, role:admin |
| GET | `/positions` | PositionController::index | auth |
| GET | `/positions/create` | PositionController::create | auth, role:admin |
| POST | `/positions/create` | PositionController::store | auth, role:admin |
| GET | `/positions/{id}/edit` | PositionController::edit | auth, role:admin |
| POST | `/positions/{id}/edit` | PositionController::update | auth, role:admin |
| POST | `/positions/{id}/delete` | PositionController::destroy | auth, role:admin |
| GET | `/device_types` | DeviceTypeController::index | auth |
| GET | `/device_types/create` | DeviceTypeController::create | auth, role:admin |
| POST | `/device_types/create` | DeviceTypeController::store | auth, role:admin |
| GET | `/device_types/{id}/edit` | DeviceTypeController::edit | auth, role:admin |
| POST | `/device_types/{id}/edit` | DeviceTypeController::update | auth, role:admin |
| POST | `/device_types/{id}/delete` | DeviceTypeController::destroy | auth, role:admin |
| GET | `/devices` | DeviceController::index | auth |
| GET | `/machines` | MachineController::index | auth |
| GET | `/machines/create` | MachineController::edit | auth |
| POST | `/machines/create` | MachineController::save | auth |
| GET | `/machines/{id}/edit` | MachineController::edit | auth |
| POST | `/machines/{id}/edit` | MachineController::save | auth |
| GET | `/machine-pm` | MachineController::plans | auth |
| GET | `/machine-pm/create` | MachineController::planEdit | auth |
| POST | `/machine-pm/create` | MachineController::planSave | auth |
| GET | `/machine-pm/{id}/edit` | MachineController::planEdit | auth |
| POST | `/machine-pm/{id}/edit` | MachineController::planSave | auth |
| GET | `/machine-pm/{id}/record` | MachineController::recordForm | auth |
| POST | `/machine-pm/{id}/record` | MachineController::recordSave | auth |
| GET | `/machine-pm/records` | MachineController::records | auth |
| GET | `/machine-pm/records/{id}` | MachineController::report | auth |
| GET | `/it-devices` | DeviceController::itDevices | auth |
| GET | `/devices/create` | DeviceController::create | auth, role:admin |
| POST | `/devices/create` | DeviceController::store | auth, role:admin |
| GET | `/devices/{id}/edit` | DeviceController::edit | auth, role:admin |
| POST | `/devices/{id}/edit` | DeviceController::update | auth, role:admin |
| POST | `/devices/{id}/delete` | DeviceController::destroy | auth, role:admin |
| GET | `/pm-schedules` | PmScheduleController::index | auth |
| GET | `/pm-schedules/create` | PmScheduleController::create | auth, role:admin,manager |
| POST | `/pm-schedules/create` | PmScheduleController::store | auth, role:admin,manager |
| GET | `/pm-schedules/{id}/edit` | PmScheduleController::edit | auth, role:admin,manager |
| POST | `/pm-schedules/{id}/edit` | PmScheduleController::update | auth, role:admin,manager |
| POST | `/pm-schedules/{id}/delete` | PmScheduleController::destroy | auth, role:admin |
| GET | `/pm-schedules/{id}/record` | PmRecordController::create | auth |
| POST | `/pm-schedules/{id}/record` | PmRecordController::store | auth |
| GET | `/pm-records` | PmRecordController::index | auth |
| GET | `/pm-records/{id}/form` | PmRecordController::form | auth |
| GET | `/pm-records/{id}/form/export` | PmRecordController::exportForm | auth |
| GET | `/pm-schedules/export` | ExportController::schedules | auth |
| GET | `/pm-records/export` | ExportController::records | auth |
| GET | `/pm-schedules/{id}/export` | ExportController::scheduleForm | auth |
| POST | `/logout` | AuthController::logout | auth |
