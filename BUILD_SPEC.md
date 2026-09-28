# Build Specification
# Offline-First Fire Extinguisher Management System

> The project specification document is the primary source for product and architectural decisions.

Build Specification: Offline-First Fire Extinguisher Management System
## 1. Mission

Build a production-oriented web application for managing fire extinguishers, their physical placements and locations, inspections and inspection history, simple maintenance/refill scheduling, documents, QR codes, and visual placement on building/floor blueprints.

The application must be designed primarily with:

Laravel 13
PHP 8.4
Laravel Livewire starter kit
Blade
Tailwind CSS
Alpine.js where useful
Vite
MariaDB 11.x
Konva.js for interactive 2D blueprints
IndexedDB, accessed through idb, for offline-first field operations
Service Worker/PWA functionality
Spatie Laravel Permission for roles and permissions
Spatie Laravel Media Library for files, photos, and media associations
Spatie Laravel Activitylog for audit/history
Spatie Laravel Data for typed application/data-transfer objects
GitHub Codespaces + Dev Container + Docker Compose

Do not introduce React, Vue, Svelte, Inertia, Next.js, a separate Node backend, Firebase, MongoDB, or another frontend framework.

Do not add additional Composer or npm dependencies beyond the dependencies explicitly selected in this specification.

The application must remain fundamentally a Laravel/Blade/Livewire application with a small, deliberately isolated JavaScript layer for:

Konva
IndexedDB
offline synchronization
QR code generation/printing
browser APIs
PWA/service-worker functionality

All user interfaces, validation messages, navigation labels, notifications, and user-facing system text must be in Brazilian Portuguese (pt-BR).

## 2. Primary Objectives

The system must support:

User authentication.
Role-based authorization.
Management of buildings and floors.
Management of physical extinguisher placements.
Management of fire extinguishers as physical equipment.
Assignment of one extinguisher to a placement.
Swapping extinguishers between placements without changing placement identity.
Reserve extinguisher management using dedicated reserve placements.
Placement of extinguishers on interactive floor-plan/blueprint canvases.
Static virtual-twin representations of placements and their currently assigned extinguishers.
Inspection execution.
Complete inspection history.
Simple refill and maintenance date management.
Photos and documents.
QR Code generation and printing.
Batch creation and management of fire extinguishers.
Batch QR Code generation and printing.
Audit/history information through Activity Log.
Offline inspection functionality.
Local storage of authorized field data in IndexedDB.
Synchronization queue for offline changes.
Reliable synchronization when connectivity returns.
Server-side authorization and validation during synchronization.
Responsive desktop/mobile/tablet UI.
Reproducible GitHub Codespaces development environment.
Automated tests for important application and synchronization behavior.

The first implementation must prioritize a solid foundation and working end-to-end workflows rather than attempting every possible enterprise feature.

## 3. Architecture

Use this overall architecture:

Laravel 13
|
+---------------------+----------------------+
|                     |                      |
Blade                Livewire              Sync API
|                     |                      |
+---------------------+----------------------+
|
Application Services
|
+-------------------+-------------------+
|                   |                   |
Eloquent          Laravel Data        Policies
|               DTOs/Contracts          |
+-------------------+-------------------+
|
MariaDB 11
|
authoritative database


Field device:

Browser / PWA
|
+----------------+----------------+
|                |                |
Blade           Livewire        JavaScript
|
+------------------+------------------+
|                 |                  |
Konva.js          IndexedDB       Service Worker
|                 |                  |
Blueprints       Offline records       caching
+ placement      + sync queue
markers


Spatie package responsibilities:

Spatie Permission
|
+-- Roles
+-- Permissions
+-- Authorization integration

Spatie Media Library
|
+-- Inspection photos
+-- Documents
+-- Blueprint files where appropriate

Spatie Activitylog
|
+-- Audit trail
+-- Before/after changes
+-- User/action/entity history

Spatie Laravel Data
|
+-- Typed DTOs
+-- Sync request/response objects
+-- Domain/application data boundaries
+-- Validated structured payloads


Important architectural rule:

MariaDB is authoritative. IndexedDB is an offline client-side store, not a replacement for MariaDB.

The domain must distinguish clearly between:

Placement
= authoritative physical managed position

Extinguisher
= physical piece of equipment occupying a Placement

Konva representation
= visual representation derived from Placement
and currently assigned Extinguisher

Cluster/overlap rendering
= temporary derived visualization

A Placement represents one physical managed extinguisher position and may have zero or one extinguisher assigned to it.

An Extinguisher may temporarily have no Placement when it is being received, processed, removed, or otherwise not currently assigned.

The Konva representation is a status-aware visual projection.

The Placement remains responsible for:

- physical identity
- Placement UUID
- Placement code
- physical x/y coordinates

The assigned Extinguisher remains responsible for:

- equipment identity
- serial number
- equipment status
- maintenance-related state
- extinguisher-specific attributes

The visual status of a Placement marker must be derived from the currently assigned Extinguisher when one exists.

If a Placement has no assigned Extinguisher, the marker must use a distinct "sem equipamento" state.

The Konva marker must never become the authoritative owner of extinguisher status and must never persist status changes directly.

## 4. Laravel Application Structure

Use normal Laravel conventions.

Organize business logic using:

Models
Form Requests
Policies
Actions/services where business logic warrants them
Livewire components
Controllers for appropriate HTTP/API endpoints
Laravel Data DTOs
Events/listeners where useful
Jobs for asynchronous work
Notifications where appropriate
Database migrations
Factories
Seeders
Policies

Avoid putting large amounts of business logic directly inside Blade templates or Livewire views.

Use Laravel validation and authorization on the server as the source of truth.

Use Laravel's localization facilities with pt_BR as the application's default locale.

Suggested additional structure:

app/
├── Actions/
├── Data/
│   ├── Blueprint/
│   ├── Extinguisher/
│   ├── Inspection/
│   ├── Placement/
│   └── Sync/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Livewire/
├── Models/
├── Policies/
├── Services/
└── ...


Laravel Data classes must be used deliberately rather than replacing every Eloquent model with a DTO.

A Data object should represent an application boundary, transfer structure, synchronization payload, form data structure, or response structure where this improves type safety and clarity.

## 5. Authentication

Start from the Laravel Livewire starter kit.

Use Laravel's supported authentication infrastructure rather than implementing authentication manually.

Required functionality:

Login
Logout
Registration if appropriate to the application's configuration
Password reset
Email verification
Password/session security
Two-factor authentication if provided by the selected starter-kit version
User profile/account functionality

Do not create a custom password authentication system.

Authentication works normally online.

Offline operation must never bypass server authentication.

Offline functionality may use previously authorized field data, but synchronization must always occur against an authenticated Laravel session/request and must be authorized again server-side.

## 6. Authorization and Roles

Install and configure:

spatie/laravel-permission


Spatie Permission is the authoritative permission system.

Do not create a separate custom roles/permissions implementation.

Initial roles:

Administrator
Technician
Maintenance
Viewer

Initial permissions include:

users.view
users.create
users.update
users.delete
users.manage

buildings.view
buildings.create
buildings.update
buildings.delete

floors.view
floors.create
floors.update
floors.delete

placements.view
placements.create
placements.update
placements.delete
placements.assign_extinguisher

blueprints.view
blueprints.create
blueprints.update
blueprints.delete

extinguishers.view
extinguishers.create
extinguishers.update
extinguishers.delete
extinguishers.batch_create

inspections.view
inspections.create
inspections.update
inspections.approve

qr_codes.view
qr_codes.generate
qr_codes.print

reports.view
documents.view
audit.view

sync.use


These are starting permissions and may evolve.

Use Spatie Permission together with Laravel Policies.

Never trust a role or permission supplied by the browser.

Field-level restrictions must be implemented server-side.

For example, a user may be allowed to update:

maintenance seal
status
inspection-related information where appropriate
notes

while being prohibited from changing:

placement
serial number
type
manufacturer
capacity
extinguishing capacity

depending on the user's permissions and business rules.

## 7. Core Domain Model

Design migrations, Eloquent models, relationships, factories, and seed data for at least:

users
roles
permissions
model_has_roles
role_has_permissions

buildings
floors

placements
blueprints

extinguisher_types
extinguisher_brands
extinguishers

inspections
inspection_items

documents

qr_label_templates

batch_jobs
batch_job_items

sync_records


Spatie Permission manages its own permission-related tables.

Spatie Activitylog manages its own activity/audit tables.

Spatie Media Library manages its media table.

Do not create custom:

audit_logs
inspection_photos
media
custom role/permission tables


when the corresponding Spatie package already provides that functionality.

Typical hierarchy:

Building
|
+-- Floor
|
+-- Placement
|
+-- 0..1 Extinguisher


A Placement is an individual physical managed position.

Example:

Building
|
+-- Floor
|
+-- RES-001
|     |
|     +-- Extinguisher
|
+-- RES-002
|     |
|     +-- Extinguisher
|
+-- RES-003
|
+-- Extinguisher


There must never be more than one extinguisher assigned to the same Placement.

## 8. Fire Extinguisher Model

Each extinguisher has a stable UUID in addition to its database primary key.

Fields:

id
uuid
placement_id
serial_number
extinguisher_type_id
extinguisher_brand_id
capacity
extinguishing_capacity
maintenance_seal
status
last_inspection_date
next_refill_period
next_maintenance_year
notes
created_at
updated_at
updated_by
deleted_at


The exact foreign-key naming may be adjusted to follow Laravel conventions.

Required presentation/input formats:

last inspection date      -> dd-mm-yyyy
next refill date          -> mm-yyyy
next maintenance date     -> yyyy

'status' represents the authoritative general condition/lifecycle state of the physical extinguisher. Allowed values are: active, maintenance, and decommissioned. status must not represent visual alert colors such as green, yellow, or red.
The application should represent these values using a PHP backed enum (ExtinguisherStatus). The MariaDB column may remain a string and should not use a database ENUM.

Store appropriate native database-compatible temporal values.

Do not store formatted date strings as the authoritative value.

The maintenance seal is a dedicated extinguisher attribute.

The extinguishing capacity is a dedicated extinguisher attribute and must not be confused with physical capacity.

Use UUIDs for synchronization.

An offline device must be able to create records without first contacting MariaDB for an auto-increment identifier.

A new extinguisher should normally be assigned to an available reserve Placement.

The equipment identity follows the extinguisher record.

The Placement identity remains independent.

## 9. Physical Placement and Virtual Twin Model

A blueprint is a visual representation of Placements and their currently assigned extinguishers.

Placement physical coordinates are authoritative.

Store:

x
y


as the authoritative physical position.

The distinction must remain:

Physical coordinates
= authoritative real-world position

Cluster/overlap rendering
= temporary derived visualization


The extinguisher does not own blueprint coordinates.

Example:

Placement EXT-042
x=100
y=100
|
+-- Extinguisher CAS-00127


After a swap:

Placement EXT-042
x=100
y=100
|
+-- Extinguisher CAS-00981


The Placement identity, coordinates, code, and physical position remain unchanged.

Placement fields:

id
uuid
code
floor_id
x
y
location_description
extinguisher_id
created_at
updated_at
updated_by
deleted_at


The physical relationship should preferably be represented through the extinguisher's placement_id, because an extinguisher can be temporarily unassigned.

Conceptually:

Placement
|
+-- hasOne/current Extinguisher

Extinguisher
|
+-- belongsTo Placement


The database must enforce that extinguishers.placement_id is unique when non-null.

When multiple Placements overlap visually, the renderer must not modify authoritative coordinates.

Konva may derive temporary offsets based on:

current zoom
scale
viewport
marker size
screen-space distance

Temporary offsets are never persisted.

## 10. Laravel Data Architecture

Use:

spatie/laravel-data


for typed DTOs and structured application data.

Create Data classes where they provide a meaningful boundary.

Examples:

app/Data/Placement/PlacementData.php
app/Data/Extinguisher/ExtinguisherData.php
app/Data/Inspection/InspectionData.php
app/Data/Inspection/InspectionItemData.php

app/Data/Sync/SyncOperationData.php
app/Data/Sync/SyncUploadData.php
app/Data/Sync/SyncResponseData.php
app/Data/Sync/SyncAcknowledgementData.php

app/Data/Blueprint/PlacementPositionData.php
app/Data/QR/QrLabelData.php
app/Data/Batch/BatchExtinguisherData.php


Use Laravel Data for:

synchronization request payloads
synchronization response payloads
typed API/application responses
blueprint position payloads
inspection form/application data
batch creation input
QR label data
field-oriented data projections
structured data crossing service boundaries

Example conceptual structure:

class SyncOperationData extends Data
{
public function __construct(
public string $operationId,
public string $entityUuid,
public string $entityType,
public string $operation,
public ?int $localVersion,
public ?string $localUpdatedAt,
public array $payload,
) {}
}


The actual implementation should use appropriate typed nested Data objects rather than unstructured arrays where practical.

Laravel Data does not replace:

Eloquent models
Form Requests
Policies
database validation
domain services

Use Eloquent for persistence and relationships.

Use Form Requests and Data validation where appropriate for HTTP/application boundaries.

The synchronization layer should use Data objects as its explicit protocol representation.

## 11. Konva.js

Install:

npm install konva


Create:

resources/js/
blueprint/
BlueprintCanvas.js
PlacementMarker.js
ExtinguisherMarker.js
BlueprintEditor.js
overlap.js


Do not allow Livewire to directly manipulate Konva's scene graph.

Use a JavaScript bridge/event mechanism.

Desired interaction:

Livewire
|
| application state/events
v
JavaScript bridge
|
v
Konva


And:

Konva
|
| click / drag / selection
v
JavaScript
|
v
Livewire/application event
|
v
Laravel


The Konva stage is JavaScript-owned state.

Support:

blueprint background
PDF-rendered blueprint background
Placement markers
assigned extinguisher information
empty Placements
reserve Placements
selection
highlighting
authorized editing mode
dragging
zoom
pan
responsive resizing
overlap detection
temporary clusters/offsets
zoom-dependent recalculation
accessible labels/tooltips
basic status indication
Placement detail UI

Placements are not draggable in normal viewing mode.

An authorized user must explicitly enter Placement Editing mode.

A drag creates a temporary proposed position.

Authoritative coordinates are not changed until the user confirms relocation.

## 12. Positioned and Unpositioned Placements

Separate:

positioned Placements
unpositioned Placements


Only positioned Placements are sent to the Konva marker renderer.

Unpositioned Placements appear in a sidebar.

When positioning a new Placement:

Create temporary Konva state.
Let the authorized user position it.
Require confirmation.
Persist authoritative x/y.
On successful persistence, remove it from the unpositioned sidebar dataset.
Add it to the positioned dataset.

If offline, the proposed physical position may be stored in IndexedDB as a pending authorized operation.

The temporary renderer position must never be confused with persisted physical coordinates.

## 13. Blueprint Storage and Media Library

Use Spatie Media Library for application-managed files.

Install:

spatie/laravel-medialibrary


Blueprints should have a model such as:

Blueprint
id
uuid
floor_id
name
file_type
width
height
version
created_at
updated_at
updated_by
deleted_at


The actual blueprint file should be stored through Media Library rather than manually managing a file_path column.

The original uploaded PDF remains the authoritative blueprint document.

Use Media Library collections such as:

blueprint
documents
inspection-photos


A Blueprint may implement:

HasMedia


and register a collection such as:

blueprint


PDF validation must occur server-side.

At minimum support:

PDF


The browser may use a PDF rendering mechanism compatible with the Laravel/Blade architecture.

PDF rendering must remain isolated from the Konva scene graph.

The original PDF is not replaced by a generated canvas image.

## 14. Media Library Architecture

Use Media Library for:

Inspection photos
Documents
Blueprint PDFs
Other explicitly supported application media

Do not create separate photo tables unless a future domain requirement genuinely requires photo-specific relational metadata.

Media Library provides:

media records
file storage
collections
conversions where needed
attachment relationships

The application must still define domain ownership clearly.

For example:

Inspection
|
+-- inspection-photos media collection

Extinguisher
|
+-- extinguisher-photos media collection

Blueprint
|
+-- blueprint media collection

Document
|
+-- documents media collection


Use Laravel filesystem configuration and Media Library storage configuration rather than manually manipulating filesystem paths throughout application code.

Validate uploads server-side.

Do not trust browser-provided MIME types.

For inspection photos:

validate file size
validate allowed file formats
optionally generate appropriate display conversions
preserve original files where required

Offline photos may initially be stored as browser Blobs in IndexedDB and uploaded during synchronization.

The basic inspection workflow must continue to work even if photo capture/storage is unavailable.

## 15. Offline-First Strategy

Offline functionality is a first-class requirement.

Do not simply add a service worker at the end.

Design:

UUIDs
synchronization
authorization
conflict handling
Media upload behavior
audit trail
IndexedDB
authentication assumptions

around offline operation.

Recommended offline scope:

assigned buildings/floors
relevant Placements
relevant blueprints
relevant extinguishers
relevant inspection/reference data
inspection forms
inspection results
notes
photos where practical
pending changes

Administrative operations may remain online unless a concrete offline requirement is identified.

## 16. IndexedDB

Install:

npm install idb


Create:

resources/js/
offline/
db.js
queue.js
sync.js


Use IndexedDB through idb.

Do not use localStorage as the primary offline database.

Suggested stores:

user_context
buildings
floors
blueprints
placements
extinguishers
inspections
inspection_items
pending_changes
sync_metadata


Each locally synchronized entity must contain enough information for:

UUID
local creation state
local update state
server version
synchronization state
timestamps
deletion state
conflict state

Offline media can be stored as Blobs in IndexedDB.

The implementation should avoid storing unnecessary sensitive data.

## 17. Offline Workflow
Online Preparation

When an authorized technician/inspector is online:

Authenticate normally.
Access assigned/authorized locations.
Download relevant field data.
Store authorized data in IndexedDB.
Make the data available to the field interface.
Offline Operation

When connectivity is unavailable:

Open the PWA/application.
Access previously synchronized authorized field data.
Open a building/floor.
View the blueprint.
View Placement markers.
View assigned extinguishers.
Select an extinguisher.
Perform an inspection.
Enter inspection results.
Add notes.
Capture/store photos where browser support permits.
Save the inspection locally.
Mark the operation as pending synchronization.
Reconnection

When connectivity returns:

Detect online state.
Process the local synchronization queue.
Send pending changes to Laravel.
Authenticate the request.
Re-check authorization.
Validate payload.
Validate referenced records.
Detect conflicts.
Apply accepted changes transactionally.
Return authoritative server state.
Mark successful operations synchronized.
Record failures/conflicts through the synchronization system and Activity Log where appropriate.
Allow the user to review unresolved conflicts.
## 18. Synchronization API

Create dedicated synchronization endpoints.

For example:

GET  /api/sync/download
POST /api/sync/upload
POST /api/sync/acknowledge


The exact design may be improved during implementation.

The protocol must be explicit and versionable.

Use Laravel Data objects for synchronization input/output.

A synchronization operation should identify:

device/session context
entity UUID
entity type
operation
local version
local timestamp
payload
client-generated operation ID
protocol version where appropriate


Never trust client-provided authorization information.

Laravel determines the authenticated user and permissions.

Example conceptual flow:

SyncUploadData
|
+-- SyncOperationData[]
|
v
Authorization
|
v
Validation
|
v
Conflict check
|
v
Transaction
|
v
Authoritative state
|
v
SyncResponseData

## 19. Synchronization Idempotency

Every offline operation must have a unique client-generated operation UUID.

The server must recognize repeated operation IDs.

A retry must not:

create duplicate inspections
create duplicate extinguishers
apply an assignment twice
duplicate a swap
duplicate a placement change

Maintain synchronization records sufficient to determine whether an operation has already been processed.

sync_records may contain concepts such as:

id
operation_uuid
entity_uuid
entity_type
user_id
operation
status
client_version
server_version
processed_at
error_code
error_message
created_at
updated_at


The exact schema may evolve.

## 20. Conflict Handling

Detect conflicts when:

client version != current server version


or when an equivalent authoritative version check determines that the record has changed since download.

Do not silently overwrite newer server data.

Initial conflict strategy:

Server wins conflicting authoritative fields.
Preserve the rejected client operation.
Record the conflict.
Inform the user.
Make conflict resolution extensible.

Inspections should be append-oriented.

Two inspectors should normally be able to create two inspection records without overwriting each other.

Use transactions for synchronization.

## 21. Offline Security

Offline authorization is not equivalent to server authorization.

Cached data represents previously authorized field access.

Laravel remains the final authority.

On synchronization, Laravel must re-check:

authenticated user
permissions
target building
target floor
target Placement
target extinguisher
requested operation
submitted data
relationship validity

A user must not gain access to another site's data merely by modifying IndexedDB.

Only synchronize records that the authenticated user is authorized to modify.

Do not store in IndexedDB:

passwords
password hashes
long-lived server secrets
API secrets
unnecessary sensitive information

Provide an application/device logout mechanism that clears locally cached sensitive field data.

Document offline security assumptions.

## 22. Inspection System

Implement practical inspection execution and complete history.

Inspection fields:

id
uuid
extinguisher_id
inspector_id
inspection_date
status
notes
created_at
updated_at


Inspection items represent checks such as:

Localização correta
Acesso desobstruído
Estado físico
Pressão
Pino de segurança
Lacre
Mangueira/bico
Etiqueta
Corrosão
Danos
Fixação
Necessidade de recarga/manutenção

Do not hard-code every inspection question into the UI if a configurable structure is more appropriate.

Inspection execution works online and offline.

Inspection history must be preserved as records.

The latest inspection date may be derived from inspection history.

The extinguisher may expose:

last_inspection_date


as a denormalized/display field if useful, but inspection history remains authoritative.

Inspection scheduling is explicitly out of scope.

Do not implement:

inspection due-date scheduling
inspection appointment scheduling
next inspection date
inspection calendar scheduling
## 23. Inspection Media

Inspection photos are managed by Media Library.

An Inspection can have a media collection:

inspection-photos


Example conceptual relationship:

Inspection
|
+-- Inspection Data
|
+-- Inspection Items
|
+-- inspection-photos


Offline workflow:

Camera
|
v
Browser Blob
|
v
IndexedDB
|
v
Pending sync operation
|
v
Laravel
|
v
Media Library


Media upload failures must not prevent synchronization of the underlying inspection where possible.

Media synchronization should be independently retryable.

## 24. Documents

Use Media Library for documents associated with application entities.

A Document domain model may contain metadata such as:

id
uuid
name
description
documentable_type
documentable_id
created_by
created_at
updated_at
deleted_at


The physical document file is managed through Media Library.

Potential document owners include:

Building
Floor
Placement
Extinguisher
Inspection

The exact ownership rules should remain simple in the first version.

Validate document uploads server-side.

## 25. Audit Trail with Activity Log

Use:

spatie/laravel-activitylog


as the application's audit/history mechanism.

Do not create a custom audit_logs table.

Important audited actions include:

User/role changes
Extinguisher creation
Extinguisher update
Extinguisher deletion/retirement
Placement creation
Placement update
Placement deletion/retirement
Placement assignment changes
Extinguisher swaps
Blueprint placement changes
Blueprint changes
Inspection creation
Inspection update
Inspection approval
Changes to next refill date
Changes to next maintenance date
QR generation/printing where useful
Batch creation
Batch updates
Synchronization operations
Synchronization conflicts
Conflict resolution

Activity Log should capture:

who
what
when
target entity
event/action
before/after changes where appropriate


Use Spatie's model activity logging facilities for relevant Eloquent models.

For example, relevant models may implement activity logging for:

Extinguisher
Placement
Inspection
Blueprint
BatchJob
Document


User/role administration should also be audited.

Do not log:

passwords
password hashes
authentication secrets
API secrets
unnecessary sensitive tokens

Activity Log is the authoritative audit mechanism.

The synchronization subsystem may maintain sync_records for synchronization state, while Activity Log records meaningful audit events.

These serve different purposes:

sync_records
= operational synchronization state

activity_log
= human/audit history


Do not duplicate all synchronization internals into the audit trail.

## 26. Placement Assignment and Swapping

A Placement may contain zero or one extinguisher.

An extinguisher may belong to zero or one Placement.

Database enforcement:

extinguishers.placement_id
UNIQUE
NULLABLE


When assigning:

Verify authorization.
Verify Placement exists.
Verify extinguisher exists.
Verify both are accessible to the user.
Verify destination Placement has no other extinguisher.
Perform assignment transactionally.
Record Activity Log event.
Return authoritative state.

When swapping:

Before:

EXT-042 -> CAS-00127
RES-003 -> CAS-00981


After:

EXT-042 -> CAS-00981
RES-003 -> CAS-00127


The following never change because of a swap:

Placement identity
Placement UUID
Placement code
Placement coordinates
Placement QR identity

The following remain attached to the physical extinguisher:

serial number
type
manufacturer
capacity
extinguishing capacity
maintenance seal
extinguisher-specific history

The swap must be performed inside a database transaction.

Activity Log must record the assignment change.

## 27. Blueprint Overlap and Virtual Twin Rendering

MariaDB:

Placement A -> x=100, y=100
Placement B -> x=100, y=100
Placement C -> x=102, y=101


Laravel returns authoritative data.

The authoritative Placement and Extinguisher state is combined into the visual marker projection.

For each Placement:

Placement coordinates determine the marker's physical position.

The currently assigned Extinguisher determines the marker's equipment-related status and status presentation.

Conceptually:

Placement
|
+-- x/y
+-- code
+-- uuid
|
+-- currently assigned Extinguisher
       |
       +-- uuid
       +-- serial number
       +-- status
       +-- maintenance state

                    ↓

          Konva Placement Marker
          |
          +-- position from Placement
          +-- identity from Placement
          +-- equipment information from Extinguisher
          +-- visual status from Extinguisher

The Konva marker visual status is derived from the currently assigned Extinguisher. It is not stored as a database field.

The Extinguisher's authoritative status and next_refill_period may contribute to the derived visual status.

The visual status must not be confused with the Extinguisher's authoritative status.

Example:

status = active + refill approaching → yellow
status = active + refill overdue → red
status = inactive/no equipment → gray
status = decommissioned → gray
status = maintenance → red

operacional
= green

atenção
= yellow

manutenção/problema/atrasado
= red

inativo/retirado
= gray

sem equipamento
= gray

The exact visual implementation may use colors, labels, borders, or combinations thereof.

Color must not be the only status indication.

The marker status is derived data and must not be persisted as a replacement for the authoritative Extinguisher status.

Status presentation must be recalculated whenever authoritative or locally synchronized Extinguisher state changes.

Overlap and clustering calculations remain independent of extinguisher status.

Konva:

calculate zoom
|
v
transform physical coordinates
|
v
detect screen-space overlap
|
v
derive temporary cluster/offset
|
v
render


The renderer must never update:

placement.x
placement.y


merely because markers overlap.

Zooming may change whether markers overlap.

Therefore overlap detection must operate against the current rendered/viewport coordinate system.

Temporary offsets are recalculated after zoom/scale changes.

Clicking a cluster should allow the user to:

inspect represented Placements
select an individual Placement
open Placement details

Reserve Placements participate in exactly the same rendering model.

## 28. Blueprint UI

The blueprint page should support:

Edifício
-> Andar
-> Planta


Display:

Placement markers
assigned extinguisher
empty Placements
reserve Placements
selected Placement
Placement details
extinguisher details

The Placement marker must visually communicate the status of its currently assigned Extinguisher.

For an assigned Extinguisher, the marker should display or expose:

- Placement code
- Extinguisher serial number
- Extinguisher status

Support:

zoom
pan
selection
explicit Placement editing mode
proposed physical position
confirmation before save
offline persistence of permitted changes
overlap/cluster rendering
zoom-dependent overlap recalculation

Status colors:

green   = operacional
yellow  = atenção
red     = manutenção/problema/atrasado
gray    = inativo/sem equipamento

The marker status is derived from the currently assigned Extinguisher.

A Placement without an Extinguisher must never inherit a stale status from a previously assigned Extinguisher.

Color must not be the only status indication.

Use:

accessible labels
tooltips
textual status

## 29. QR Code Generator and Printing

Implement QR Code generation for:

individual extinguishers
individual Placements
multiple selected extinguishers
multiple selected Placements

A QR code must use a stable, non-secret identifier.

For a physical installed position, a Placement-based QR identity is preferred because it remains valid when the extinguisher is swapped.

Example conceptual URL:

/app/placements/{placement-uuid}


or an equivalent application route.

Do not encode sensitive information.

Support:

individual QR generation
batch QR generation
QR preview
label templates
label preview
multiple labels per page
browser printing
PDF/browser print output where appropriate

Label fields may include:

QR Code
Código da posição
Número do casco


QR generation/printing remains an application feature.

Do not introduce a separate frontend application.

## 30. QR Label Templates

Use:

qr_label_templates


for reusable label configurations.

A template may contain:

id
uuid
name
description
configuration
created_at
updated_at
created_by
updated_by


The exact configuration format may use structured JSON.

Laravel Data should be used to represent validated template configuration in application code.

Example:

QrLabelData
QrLabelTemplateData


QR generation and print data should remain isolated under:

resources/js/qr/


where browser-side orchestration is necessary.

## 31. Batch Operations

Support:

Creation of many extinguishers from a template.
Creation of reserve Placements.
Sequential/configurable Placement codes.
Common fields.
Extinguisher type.
Brand.
Capacity.
Building/floor.
Individual reserve Placement assignment.
Batch QR generation.
Batch QR printing.
Authorized batch updates.

Example:

Quantidade: 20
Tipo: ABC
Marca: Exemplo
Capacidade: 6 kg
Edifício: Bloco A
Andar: Térreo
Posições de reserva: gerar/selecionar


The application creates individual extinguisher records.

Each extinguisher gets:

unique UUID
unique identity
individual Placement assignment

No Placement may receive multiple extinguishers.

Use Laravel Jobs when batch workloads justify asynchronous processing.

Batch states:

Aguardando
Em processamento
Concluído
Concluído com erros
Falhou


Use Laravel Data for structured batch input/output where useful.

All batch operations must respect:

authorization
validation
transactions
Activity Log
UUID generation
unique constraints
## 32. Database and Migrations

MariaDB is the authoritative production database.

Configuration:

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=fire_extinguisher
DB_USERNAME=laravel
DB_PASSWORD=...


Use proper indexes and foreign keys.

Important indexes include:

UUIDs
Placement codes
serial numbers
foreign keys
inspection dates
next refill date
next maintenance date
synchronization identifiers
version fields
timestamps used for synchronization

Enforce:

one Placement -> maximum one Extinguisher


through a unique nullable placement_id on extinguishers.

Use soft deletes where appropriate.

Do not use SQLite as the primary production database model.

Tests must exercise MariaDB-compatible behavior.

## 33. Codespaces Environment

Create:

.devcontainer/
devcontainer.json
Dockerfile

compose.yaml


The Codespace must provide:

PHP 8.4
Composer 2
Node.js 22 LTS
npm
Git
required PHP extensions

MariaDB runs as a separate Docker Compose service.

Architecture:

Codespace
|
+-- PHP/Laravel development container
|
+-- MariaDB container


The environment must be reproducible.

## 34. PHP Extensions

Provide at least:

php-cli
php-common
php-curl
php-mbstring
php-xml
php-zip
php-bcmath
php-intl
php-gd
php-mysql
php-opcache
php-fileinfo
php-readline


Add extensions only when an actual dependency requires them.

## 35. Dependency Set
PHP/Composer

Core:

Laravel 13
Laravel Livewire starter kit
Laravel Fortify through starter kit
spatie/laravel-permission
spatie/laravel-medialibrary
spatie/laravel-activitylog
spatie/laravel-data


Do not add another Composer dependency at this stage.

NPM

Specialized additions:

konva
idb


Add QR-related JavaScript dependency only if the implementation demonstrates that one is required.

No other dependency is to be introduced at this stage.

## 36. Suggested Project Structure
fire-extinguisher-management/
│
├── .devcontainer/
│   ├── devcontainer.json
│   └── Dockerfile
│
├── app/
│   ├── Actions/
│   ├── Data/
│   │   ├── Blueprint/
│   │   ├── Batch/
│   │   ├── Extinguisher/
│   │   ├── Inspection/
│   │   ├── Placement/
│   │   ├── QR/
│   │   └── Sync/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── ...
│   ├── Livewire/
│   ├── Models/
│   ├── Policies/
│   ├── Services/
│   └── ...
│
├── bootstrap/
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── public/
│   ├── manifest.json
│   └── ...
│
├── resources/
│   ├── css/
│   ├── js/
│   │   ├── blueprint/
│   │   ├── offline/
│   │   └── qr/
│   └── views/
│
├── storage/
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── ...
│
├── compose.yaml
├── composer.json
├── package.json
├── vite.config.js
└── .env.example


Follow Laravel conventions where preferable.

## 37. UI Requirements

Create a clean responsive administrative interface using:

Blade
Livewire
Tailwind
Alpine.js where useful

All UI text must be Brazilian Portuguese.

Main navigation, according to permissions:

Painel
Plantas
Extintores
Edifícios
Andares
Inspeções
Documentos
Códigos QR
Relatórios
Usuários
Configurações

Dashboard:

Total de extintores
Extintores operacionais
Inspeções realizadas recentemente
Histórico de inspeções
Recargas próximas
Manutenções próximas
Sincronizações pendentes
Status online/offline

Do not display unauthorized actions.

Do not display inspection scheduling or inspection due-date indicators.

## 38. Localization

Configure:

APP_LOCALE=pt_BR


Use Brazilian Portuguese conventions.

Examples:

dd-mm-yyyy
mm-yyyy
yyyy


All validation and interface messages must be Portuguese.

Examples:

Online
Offline
Sincronização pendente
Sincronização em andamento
Sincronização concluída
Falha na sincronização
Conflito requer atenção


Do not expose English technical messages directly to end users.

## 39. PWA and Service Worker

Provide:

manifest.json
service worker


Implement:

application shell caching
static resource caching
online/offline detection
conservative authenticated-resource caching

Do not indiscriminately cache sensitive server responses.

Offline field data belongs in IndexedDB.

Service Worker cache is not the authoritative offline database.

The UI must communicate connectivity and synchronization status in Brazilian Portuguese.

## 40. Data Synchronization and Activity Logging

Synchronization has two separate concerns:

Synchronization state

Handled by:

sync_records
IndexedDB pending_changes
sync_metadata


These answer:

Has the operation been processed?
Did it fail?
Is it pending?
What version was involved?
Is there a conflict?
Audit history

Handled by:

Spatie Activitylog


This answers:

Who changed the Placement?
Who swapped the extinguishers?
Who approved the inspection?
What changed?
When did it happen?

Do not use Activity Log as a replacement for the synchronization queue.

Do not use sync_records as a replacement for an audit history.

## 41. Testing

Create meaningful automated tests.

Authentication

Test:

Login
Logout
protected areas
unauthorized access
Authorization

Test:

role permissions
restricted resources
field-level restrictions
synchronization authorization
Placements

Test:

create
read
update
delete/retire
UUID
floor relationship
physical coordinates
unique extinguisher assignment
reserve Placements
vacant installed Placements
authorized assignment
unauthorized assignment
Extinguishers

Test:

create
read
update
delete/retire
UUID
Placement relationship
maintenance seal
extinguishing capacity
refill date
maintenance date
inspection date
reserve assignment
movement between reserve/installed positions
Swapping

Test:

failing extinguisher replacement
reserve extinguisher assignment
installed Placement code unchanged
installed Placement coordinates unchanged
reserve Placement code unchanged
physical extinguisher attributes preserved
removed extinguisher assignment
transaction integrity
authorization
Activity Log entry
Blueprint

Test:

authorized position update
unauthorized position update
coordinate persistence
overlapping Placements
coordinates unchanged by overlap rendering
zoom-dependent overlap calculation
PDF validation
PDF storage through Media Library
reserve Placement rendering

JavaScript/browser tests where practical should verify:

cluster calculations
zoom-dependent overlap calculations
temporary offsets
no mutation of authoritative coordinates
Media

Test:

blueprint PDF upload
inspection photo upload
extinguisher photo upload
invalid file rejection
file-size validation
Media Library association
Inspections

Test:

valid inspection
invalid inspection
authorization
inspection date
inspection history
multiple inspections
no overwrite
offline inspection synchronization
QR

Test:

individual QR
stable Placement QR identity
QR identity after swap
batch generation
label data
label template rendering
authorization
Batch

Test:

template validation
unique UUIDs
duplicate serial prevention
reserve Placement assignment
one extinguisher per Placement
job status
failure handling
authorization
Activity Log
Synchronization

Test:

offline-created record
offline update
offline inspection
offline Placement update
duplicate operation retry
idempotency
unauthorized synchronization
invalid payload
stale record
conflict
server-authoritative state
acknowledgement
failed synchronization
Placement assignment synchronization
extinguisher swap synchronization
media synchronization where practical
Activity Log

Test that important actions create expected activity records.

Examples:

Placement assignment
extinguisher update
swap
inspection creation
blueprint position change
batch creation
Laravel Data

Test:

valid Data construction
invalid Data validation
synchronization payload transformation
authoritative response transformation
Database

Run tests against MariaDB-compatible configuration.

Do not depend on SQLite-specific behavior.

## 42. Seed/Demo Data

Provide useful development seeders.

Include:

multiple buildings
multiple floors
representative PDF blueprint
installed Placements
reserve Placements
empty reserve Placements
extinguisher types
manufacturers/brands
10–20 sample extinguishers
inspection history
refill dates
maintenance dates
QR label templates
users for each role

Demo data should demonstrate:

installed Placement -> extinguisher

reserve Placement -> extinguisher

reserve Placement -> no extinguisher

multiple reserve Placements


Create development-only test accounts.

Do not hard-code production passwords or secrets.

Seed representative media where practical.

The representative blueprint PDF should be suitable for development/testing and must not contain confidential real-world information.

## 43. Developer Experience

The expected workflow should be approximately:

docker compose up -d

composer install

npm install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

npm run build


Provide an appropriate Laravel development command.

Codespaces may automate safe initialization through postCreateCommand.

Initialization must not be destructive.

Useful verification:

php -v
composer --version
node --version
npm --version
docker compose ps
php artisan about
php artisan migrate:status

## 44. Environment Configuration

Provide:

.env.example


Document:

application URL
application locale
database
filesystem
mail
queue
cache
synchronization configuration where required
Media Library filesystem configuration where appropriate

Use development-only database credentials.

Never commit secrets.

## 45. Optional Services

Do not add Redis initially unless genuinely required.

Do not add:

Elasticsearch
Kafka
RabbitMQ
separate API server
separate frontend server
Kubernetes

Possible future additions:

Redis
Mailpit
queue workers
Horizon
scheduled jobs
image-processing service

But they must not be introduced in the initial implementation without a concrete requirement.

## 46. Service Boundaries

Keep concerns separated.

Laravel responsible for:

authentication
authorization
policies
validation
business rules
database
synchronization API
Media Library
Activity Log
batch jobs
QR label data
filesystem
transactions
authoritative Extinguisher status (active, maintenance, decommissioned)
derived visual-status calculation based on Extinguisher status and next_refill_period
providing status-aware Placement projections to the frontend


Laravel Data responsible for:

typed application DTOs
synchronization payloads
synchronization responses
structured form/application data
blueprint position data
QR label data
batch input/output structures


Livewire responsible for:

interactive server-driven UI
server-side application state
forms
user interactions
communication with Laravel application services


Blade responsible for:

presentation
Brazilian Portuguese localization
accessible UI


Konva responsible for:

blueprint visualization
marker rendering
selection
zoom
pan
temporary overlap/cluster rendering
displaying the status of the currently assigned Extinguisher


IndexedDB responsible for:

offline field persistence
local synchronized records
pending operations
offline media Blobs where practical


Service Worker responsible for:

application shell
static resource caching
offline application bootstrapping
connectivity-related browser functionality


sync.js responsible for:

synchronization orchestration
queue processing
retries
online detection
synchronization state

It must not contain Laravel business rules.


Media Library responsible for:

managed file/media storage
media associations
media collections
file conversions where required


Activity Log responsible for:

audit/history events
user/action/entity history
before/after information where appropriate


QR responsible for:

QR generation orchestration
QR preview
browser print orchestration


Do not put database/business rules into JavaScript.

Do not put Konva internals into PHP.

Do not make Livewire manage the Konva scene graph.

Do not treat cluster coordinates as domain data.

Do not make a Placement responsible for extinguisher-specific attributes.

Do not make an extinguisher responsible for blueprint coordinates.

## 47. Documentation

Create/update README.md with:

project overview
architecture
requirements
Codespaces setup
local development setup
database configuration
authentication
roles/permissions
Spatie Permission configuration
Placement architecture
reserve Placement architecture
extinguisher/Placement relationship
extinguisher swapping
blueprint architecture
physical-coordinate model
virtual-twin model
PDF blueprint handling
Media Library architecture
inspection photos
document management
Activity Log architecture
Laravel Data architecture
overlap/cluster rendering
offline architecture
IndexedDB structure
synchronization protocol
conflict handling
inspection execution/history
QR Code generation
QR label templates
batch operations
testing
seed data
common development commands
Brazilian Portuguese localization
security assumptions
known limitations
future improvements

Explicitly document:

Placement
= authoritative physical managed position

Extinguisher
= physical equipment assigned to a Placement

One Placement
= zero or one Extinguisher

Reserve inventory
= individual reserve Placements

Physical coordinates
= authoritative real-world Placement position

Cluster/overlap offsets
= temporary renderer-derived values

Swap
= changing extinguisher assignment between existing Placements

Placement identity
= does not change during a swap


Also document:

Inspection functionality
= execution
+ results
+ photos
+ history

Inspection scheduling
= explicitly out of scope


Document the responsibilities of:

sync_records
= synchronization state

Activity Log
= audit/history

Media Library
= file/media management

Laravel Data
= typed data/application boundaries

## 48. Implementation Strategy

Implement incrementally.

Phase 1 — Environment

Create:

Codespace
Dev Container
Docker Compose
MariaDB

Verify:

php -v
composer --version
node --version
npm --version
docker compose ps


Verify Laravel connects to MariaDB.

Phase 2 — Laravel Foundation

Install/configure:

Laravel
Livewire starter kit
authentication
Tailwind/Vite
Spatie Permission
Spatie Media Library
Spatie Activitylog
Spatie Laravel Data
Brazilian Portuguese localization

Create migrations/models/factories/seeders.

Configure:

filesystem
media collections
Activity Log
roles
permissions
Data classes
Phase 3 — Core Domain

Implement:

buildings
floors
Placements
extinguisher types
brands
extinguishers

Create CRUD UI.

Implement:

última inspeção
próxima recarga
próxima manutenção


with:

dd-mm-yyyy
mm-yyyy
yyyy


Implement:

one-to-one assignment
reserve Placements
assignment
swap workflows
Activity Log
Phase 4 — Media and Documents

Implement Media Library integration.

Support:

blueprint PDFs
inspection photos
documents

Validate files.

Create media collections.

Verify media lifecycle and authorization.

Phase 5 — Blueprint

Implement:

blueprint upload
PDF storage
PDF rendering
floor blueprint display
Konva stage
Placement markers
extinguisher information
empty/reserve Placement rendering
selection
zoom
pan
physical coordinate persistence
explicit editing mode
overlap detection
temporary clustering
zoom-dependent recalculation

Do not persist derived cluster coordinates.

Phase 6 — Inspections

Implement online inspection execution first.

Implement:

inspection form
inspection items
results
notes
history
photos
approval where authorized

Do not implement inspection scheduling.

Phase 7 — QR Codes and Labels

Implement:

individual QR
Placement QR
extinguisher QR
label templates
preview
printing
batch QR generation
batch printing

Ensure Placement QR identity remains stable through extinguisher swaps.

Phase 8 — Batch Operations

Implement:

extinguisher templates
reserve Placement generation
batch creation
validation
unique UUIDs
assignment
jobs where justified
progress/status
Activity Log
Phase 9 — Offline Storage

Implement:

IndexedDB wrapper
data hydration
pending operation queue
cached field data
offline inspections
offline photos where practical


Use idb.

Phase 10 — Synchronization

Implement:

sync download
sync upload
operation UUIDs
idempotency
Laravel Data sync payloads
authorization
validation
conflict detection
acknowledgements
authoritative response state
synchronization UI
inspection synchronization
permitted Placement changes
permitted assignment changes
permitted swaps
media synchronization
Phase 11 — PWA

Implement:

manifest
service worker
application shell caching
offline/online state
conservative caching strategy
Phase 12 — Audit and Reporting

Use Activity Log for audit functionality.

Implement:

audit views
initial reports
synchronization history where useful
relevant activity filtering

Do not build a parallel audit table.

Phase 13 — Testing and Documentation

Complete:

feature tests
unit tests
synchronization tests
authorization tests
media tests
Activity Log tests
Laravel Data tests
browser/manual offline testing
PDF testing
printing testing
PWA testing

Update README.

## 49. Definition of Done

A developer must be able to create a GitHub Codespace and run the application without manually installing PHP, Composer, Node, or MariaDB outside the configured environment.

The following end-to-end scenario must work:

User logs in online.

User has the Technician role.

Technician opens a building and floor.

Blueprint is displayed, including a supported PDF blueprint.

Placement virtual twins appear at authoritative physical coordinates.

Overlapping Placements are visually clustered/offset without changing authoritative coordinates.

Technician selects a Placement.

Technician sees its currently assigned extinguisher.

Technician opens its inspection form.

Browser loses network connectivity.

Technician continues accessing previously synchronized authorized field data.

Technician performs an inspection.

Inspection is stored in IndexedDB.

UI shows:

Sincronização pendente


Network connection returns.

Synchronization starts automatically or manually.

Laravel authenticates the request.

Laravel authorizes the request.

Laravel validates the inspection.

Inspection is written to MariaDB.

Media associated with the inspection is synchronized through Media Library where applicable.

Client receives authoritative acknowledgement.

Local operation is marked synchronized.

Refreshing the online application displays the inspection in history.

Activity Log contains the relevant audit event.

Unauthorized users cannot perform the same operation.

A duplicate synchronization request does not create a duplicate inspection.

Authorized user generates and prints a QR label.

Authorized user creates multiple extinguishers from a batch template.

New extinguishers are assigned to individual reserve Placements.

Authorized user swaps an installed failing extinguisher with an available reserve extinguisher.

Installed Placement code remains unchanged.

Installed Placement coordinates remain unchanged.

Replacement extinguisher's serial number and physical attributes remain associated with that extinguisher.

Removed extinguisher can be assigned to an appropriate reserve Placement.

No Placement contains more than one extinguisher.

Authorized user can generate and print multiple QR labels.

All UI is Brazilian Portuguese.

## 50. Important Implementation Constraints

Do not over-engineer the first version.

Prefer:

Laravel + Blade + Livewire


over introducing a SPA.

Prefer:

MariaDB


over another database.

Prefer:

IndexedDB + idb


over a complete offline database framework.

Prefer:

Konva.js


for blueprint visualization.

Prefer Laravel-native:

authentication
validation
policies
filesystem
queues
events
notifications
scheduling
transactions

Use the selected Spatie packages where they provide a concrete architectural responsibility:

Spatie Permission
-> authorization

Spatie Media Library
-> media/files

Spatie Activitylog
-> audit/history

Spatie Laravel Data
-> typed DTO/data boundaries


Do not introduce another package to duplicate these responsibilities.

Only add a dependency if this specification is explicitly revised.

## 51. Final Architectural Principle

The application should feel like a conventional Laravel application first and an offline/PWA application second.

Normal online application:

Blade
+
Livewire
+
Laravel
+
Eloquent
+
MariaDB


Application services and structured data:

Laravel Data
+
Policies
+
Actions/Services


Operational extensions:

Spatie Permission
+
Spatie Media Library
+
Spatie Activitylog


Specialized field experience:

Konva.js
+
IndexedDB
+
Service Worker
+
Synchronization API


Additional capabilities:

QR Code generation
+
Label templates
+
Batch operations


Overall:

┌────────────────────────────┐
│        Laravel 13          │
│                            │
│ Auth / Policies            │
│ Livewire / Blade            │
│ Business Logic              │
│ Laravel Data                │
│ Sync API                    │
│ Batch Jobs                  │
│ QR / Labels                 │
└──────────────┬─────────────┘
│
┌─────────────────────┼─────────────────────┐
│                     │                     │
▼                     ▼                     ▼
Spatie Permission      Media Library          Activity Log
Authorization          Files/Media             Audit
│                     │                     │
└─────────────────────┼─────────────────────┘
│
┌──────▼──────┐
│   MariaDB   │
│ authoritative│
│   database  │
└─────────────┘


FIELD DEVICE / BROWSER

┌───────────────────────┐
│      Blade UI         │
│          +            │
│      Livewire         │
│          +            │
│      Konva.js         │
│          +            │
│     IndexedDB         │
│          +            │
│   Service Worker      │
│          +            │
│    QR / Printing      │
└───────────────────────┘


The application's fundamental domain remains:

Placement
= one authoritative managed physical position

Extinguisher
= one physical piece of equipment

Inspection
= one executed inspection record

Floor
|
+-- Placement
|
+-- 0..1 Extinguisher


A new extinguisher normally enters through a reserve Placement.

Example:

Before:

EXT-042 → CAS-00127
RES-003 → CAS-00981

After:

EXT-042 → CAS-00981
RES-003 → CAS-00127


The Placement's:

identity
UUID
code
QR identity
physical coordinates

remain unchanged.

The extinguisher's:

serial number
type
manufacturer
capacity
extinguishing capacity
maintenance seal
equipment history

remain attached to the physical extinguisher.

Blueprint overlap visualization is derived from authoritative Placement coordinates and current Konva zoom/scale.

Temporary visual offsets are never persisted as physical coordinates.

Inspection functionality consists of:

inspection execution
+
inspection results
+
inspection photos
+
inspection history


It does not include inspection scheduling.

The four selected Spatie packages have clear, non-overlapping responsibilities:

Permission
= Who is allowed to do something?

Media Library
= Where/how are application files and photos managed?

Activity Log
= What happened, who did it, and when?

Laravel Data
= What structured, typed data crosses application boundaries?


Build the project in small, verifiable increments.

After each major phase:

Run relevant automated tests.
Verify Laravel boots.
Verify MariaDB connectivity.
Verify authorization.
Verify affected UI.
Verify no architectural boundary has been violated.

For true offline behavior, PDF rendering, browser printing, media capture, service-worker behavior, and browser-specific PWA behavior, supplement automated tests with documented browser/manual tests.

If an implementation decision is ambiguous, choose the simplest Laravel-native solution that preserves the architecture above.

If a requirement cannot be implemented reliably with this architecture, document the technical issue rather than silently introducing another framework, package, database, or architectural dependency.
