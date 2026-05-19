# EFL Access Portal — Execution Plan
**Basado en:** ADMIN_PANEL_LLD.md v1.0  
**Fecha:** May 2026  
**Revisión:** Veredictos R1–R6 + ajustes post-Fase 1

---

## Decisiones de Arquitectura Incorporadas

| Riesgo | Veredicto | Impacto en el plan |
|--------|-----------|-------------------|
| R1 — Spatie vs `users.role` | **Eliminado** — usar `users.role` directo + Laravel Gates | Se eliminan 2 paquetes y el RolePermissionSeeder |
| R2 — `is_active` sin migración | **Cerrado** — columna ya existe en DB compartida | Sin acción |
| R3 — Migraciones en tablas compartidas | **Cerrado** — `countries`, `country_id`, `lat/lng` ya existen en la API | El portal **no crea ninguna migración** |
| R4 — MapWidget Leaflet | **Proceder** — widget custom View es el patrón correcto | Fase 6, último widget |
| R5 — Panel en path `/` | **Confirmar** — intencional, VPN protege | Sin cambios |
| R6 — Export con filtros | **Proceder** — usar `ExportAction` nativo de Filament v5 (OpenSpout incluido) | Fase 5.5 |

## Ajustes Reales de la Fase 1 (post-instalación)

| Item | Planificado | Real | Razón |
|------|-------------|------|-------|
| Laravel | v12 | **v13.11.1** | v13 es el actual en mayo 2026 |
| Filament | v3 | **v5.6.3** | v3 bloqueado por advisory `GHSA-9h9q-qhxg-89xr`; v5 es el actual |
| Livewire | v3 | **v4.3.0** | Viene como dependencia de Filament v5 |
| `maatwebsite/excel` | Instalado | **Eliminado** | No soporta PHP 8.5; reemplazado por `ExportAction` nativo de Filament v5 (usa `openspout` ya incluido) |
| `ext-intl` | No mencionado | **Habilitada en `C:\php\8.5\php.ini`** | Requerida por Filament v5.3.5+ |
| Migraciones default | Borrar | **Borradas** | `create_users_table`, `create_cache_table`, `create_jobs_table` eliminadas |

### Por qué el portal NO crea migraciones

La DB compartida (`servercpanel_privateapi`) ya contiene **todo** lo que el portal necesita, creado por la API:
- Tabla `countries` con `id`, `name`, `code`, `flag_emoji`, `is_active`
- Columna `country_id` en `stations` y `users`
- Columnas `latitude`/`longitude` en `stations`
- Columna `is_active` en `users`

Crear migraciones desde el portal intentaría duplicar estructuras existentes → error. Solo se necesitan modelos Eloquent que lean/escriban esas tablas.

### Cambio en Export (Fase 5.5)

En lugar de `maatwebsite/excel`, usar el sistema nativo de Filament v5:

```php
// En lugar de ExcelExport de Maatwebsite:
use Filament\Actions\Exports\ExportAction;
use Filament\Actions\Exports\Exporter;

// En VisitResource::getHeaderActions()
ExportAction::make()->exporter(VisitExporter::class)

// app/Filament/Exports/VisitExporter.php extiende Exporter (no Maatwebsite)
```

El comportamiento es idéntico al descrito en el LLD — exporta con filtros activos de la tabla.

---

## Consecuencia clave de R3: cero migraciones

Dado que la DB compartida ya tiene todas las tablas necesarias, el portal **no debe ejecutar `php artisan migrate`** sobre la DB de producción. En cambio:

1. Borrar todas las migraciones default de Laravel que genera `create-project`
2. No crear ninguna migración nueva
3. El comando `php artisan migrate` solo puede usarse si apunta a una DB de prueba vacía

---

## FASE 1 — Bootstrap del Proyecto ✅ COMPLETADA
**Duración real:** ~2 h  
**Stack instalado:** Laravel 13.11.1 · Filament v5.6.3 · Livewire v4.3.0

| # | Tarea | Estado | Nota |
|---|-------|--------|------|
| 1.1 | Crear proyecto Laravel 13 | ✅ | Instalado en directorio existente via dir temporal |
| 1.2 | Habilitar `ext-intl` en `C:\php\8.5\php.ini` | ✅ | Requerida por Filament v5; estaba comentada |
| 1.3 | Instalar Filament v5.6.3 | ✅ | `composer require filament/filament:"^5.0"` |
| 1.4 | Ejecutar `php artisan filament:install --panels` | ✅ | Creó `AdminPanelProvider`, publicó assets |
| 1.5 | ~~Instalar Maatwebsite Excel~~ | ❌ Eliminado | No soporta PHP 8.5; usar `ExportAction` nativo de Filament v5 |
| 1.6 | Crear `.env` con credenciales MySQL | ✅ | APP_DEBUG=true para desarrollo local |
| 1.7 | Crear `.gitignore` | ✅ | Incluye `ADMIN_PANEL_LLD.md` y `/.claude/` |
| 1.8 | Crear `public/web.config` | ✅ | Rewrite rules + hiddenSegments para IIS |
| 1.9 | Actualizar `public/.htaccess` | ✅ | Agregado bloqueo de archivos sensibles |
| 1.10 | Borrar migraciones default de Laravel | ✅ | `create_users_table`, `create_cache_table`, `create_jobs_table` eliminadas |

**Checkpoint verificado:** `php artisan about` muestra `EFL Access Portal · Laravel 13.11.1 · Filament v5.6.3`  
**Nota DB:** `migrate:status` falla con "Access denied" en dev local — normal, la DB solo acepta conexiones desde el servidor cPanel. Se verifica al desplegar.

---

## FASE 2 — Modelos
**Duración estimada:** 1.5–2 h  
**Dependencias:** Fase 1 completa, conexión DB verificada

No hay migraciones que crear — la DB compartida ya tiene todo. Solo modelos.

### 2.1 — Orden de creación (por dependencias de relaciones)

```
Country.php          ← sin dependencias
User.php             ← belongsTo Country, implementa FilamentUser
Station.php          ← belongsTo Country, hasMany Visit + StationDeviceLog
StationDeviceLog.php ← belongsTo Station
Visitor.php          ← hasMany Visit
Visit.php            ← belongsTo Station + Visitor, hasMany VisitImage
VisitImage.php       ← belongsTo Visit
```

### 2.2 — Propiedades base en todos los modelos

```php
protected $connection = 'mysql';
protected $primaryKey = 'id';
public $incrementing  = false;
protected $keyType    = 'string'; // UUID
```

### 2.3 — User model (sin Spatie)

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'country_manager', 'viewer'])
            && (bool) $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canWrite(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'country_manager']);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
```

> `isSuperAdmin()` y `canWrite()` son helpers de instancia — se usan dentro de Resources y Widgets para evitar repetir el `in_array` en cada lugar.

### 2.4 — Seeders (única interacción con DB en esta fase)

El seeder **solo debe correr una vez** en cada entorno. No hace `updateOrCreate` — crea registros nuevos o falla limpiamente si ya existen.

| Seeder | Contenido |
|--------|-----------|
| `SuperAdminSeeder` | Usuario `admin@efltrackingsystem.com` con `role = 'super_admin'`, `is_active = 1` |

```php
// SuperAdminSeeder
User::create([
    'id'         => (string) Str::uuid(),
    'name'       => 'EFL Super Admin',
    'email'      => 'admin@efltrackingsystem.com',
    'password'   => Hash::make('EFL@Admin2026!'),
    'role'       => 'super_admin',
    'country_id' => null,
    'is_active'  => true,
]);
```

> No hay `CountrySeeder` — los países ya existen en la DB compartida de la API.

**Checkpoint:** `php artisan db:seed --class=SuperAdminSeeder`. Login en `http://localhost/` con las credenciales debe abrir el panel.

---

## FASE 3 — Configuración del Panel Filament
**Duración estimada:** 30 min  
**Dependencias:** Fase 2 completa

Editar `app/Providers/Filament/AdminPanelProvider.php`:

```php
->id('admin')
->path('/')
->login()
->colors(['primary' => Color::Orange])
->navigationGroups(['Operaciones', 'Gestión', 'Sistema'])
->middleware(['web'])
->authMiddleware(['auth'])
// Sin ->plugin(SpatieLaravelPermissionPlugin) — no se usa Spatie
```

**Checkpoint:** El panel abre en `/`, muestra login con colores naranja, y los grupos de navegación aparecen vacíos (aún sin Resources).

---

## FASE 4 — Gates y Trait de Scoping
**Duración estimada:** 45 min  
**Dependencias:** Fase 3 completa

Toda la lógica de acceso vive en dos lugares: Gates en `AppServiceProvider` y un trait reutilizable.

### 4.1 — Gates en `AppServiceProvider`

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Acceso total — ve todos los países
    Gate::define('is-super-admin', fn(User $user) =>
        $user->role === 'super_admin'
    );

    // Puede crear/editar (super_admin + country_manager)
    Gate::define('can-write', fn(User $user) =>
        in_array($user->role, ['super_admin', 'admin', 'country_manager'])
    );
}
```

### 4.2 — Trait `ScopedByCountry`

Archivo: `app/Filament/Traits/ScopedByCountry.php`

```php
trait ScopedByCountry
{
    public static function applyCountryScope(Builder $query, string $relation = 'station'): Builder
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas($relation, fn($q) =>
            $q->where('country_id', $user->country_id)
        );
    }
}
```

Uso en cada Resource que necesite scope:
```php
public static function getEloquentQuery(): Builder
{
    return static::applyCountryScope(parent::getEloquentQuery());
}
```

Para `StationResource` (relación directa, no nested):
```php
public static function getEloquentQuery(): Builder
{
    $user = auth()->user();
    $query = parent::getEloquentQuery();
    return $user->isSuperAdmin() ? $query : $query->where('country_id', $user->country_id);
}
```

---

## FASE 5 — Resources de Filament
**Duración estimada:** 6–8 h  
**Dependencias:** Fases 3 y 4 completas

Implementar en orden de menor a mayor complejidad:

### 5.1 `CountryResource` — Sistema (solo super_admin)

- Grupo: `Sistema`
- Visible solo si `Gate::allows('is-super-admin')`
- CRUD: nombre, código (5 chars), flag_emoji, is_active
- Sin scope de país

```php
public static function canViewAny(): bool
{
    return Gate::allows('is-super-admin');
}
```

### 5.2 `UserResource` — Sistema (solo super_admin)

- Grupo: `Sistema`
- Visible solo si `Gate::allows('is-super-admin')`
- Campo `role`: `Select` con opciones `['super_admin', 'country_manager', 'viewer']`
- Campo `country_id`: requerido cuando `role != 'super_admin'`
- Validación condicional:

```php
Select::make('role')
    ->options([
        'super_admin'     => 'Super Admin',
        'country_manager' => 'Country Manager',
        'viewer'          => 'Viewer',
    ])
    ->live(),

Select::make('country_id')
    ->relationship('country', 'name')
    ->requiredUnless('role', 'super_admin')
    ->hidden(fn(Get $get) => $get('role') === 'super_admin'),
```

### 5.3 `VisitorResource` — Gestión

- Grupo: `Gestión`
- Scope: visitante no tiene `country_id` directo — filtrar via visitas:
  ```php
  public static function getEloquentQuery(): Builder
  {
      $user = auth()->user();
      $query = parent::getEloquentQuery()->withCount('visits');
      if ($user->isSuperAdmin()) return $query;
      return $query->whereHas('visits.station', fn($q) =>
          $q->where('country_id', $user->country_id)
      );
  }
  ```
- Columnas calculadas: `visits_count` (via `withCount`), `last_visit` (via subquery o `with`)
- Acciones de escritura ocultas si `!auth()->user()->canWrite()`

### 5.4 `StationResource` — Gestión

- Grupo: `Gestión`
- Scope: `where('country_id', $user->country_id)` si no es super_admin
- Acción `Reset dispositivo`:
  - Solo visible si `canWrite()`
  - Llama a `$station->unregisterDevice('admin_reset')` — definir este método en `Station` model
  - Requiere confirmación con modal
- Acción `Ver historial de dispositivos`:
  - `ViewAction` que muestra tabla inline de `StationDeviceLog` filtrada por `station_id`
- PIN único al crear: generar en `mutateFormDataBeforeCreate`:
  ```php
  protected function mutateFormDataBeforeCreate(array $data): array
  {
      do {
          $pin = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
      } while (Station::where('pin', $pin)->exists());
      $data['pin'] = $pin;
      return $data;
  }
  ```

### 5.5 `VisitResource` — Operaciones (más compleja)

- Grupo: `Operaciones`
- Scope: `applyCountryScope(query, 'station')`
- Columnas:
  - `duration`: calculada como `TIMESTAMPDIFF(MINUTE, check_in, check_out)` via columna virtual o accessor
  - Estado: badge coloreado (`activo` = verde, `completado` = gris)
- Filtros: país (solo super_admin), estación, estado, fecha desde/hasta, tipo de visitante
- Acción `Ver detalle`: modal con datos completos + imágenes de `visit_images` en grid

**Exportación con filtros activos:**

```php
// En VisitResource::table()
ExportAction::make()
    ->exports([
        ExcelExport::make()
            ->fromTable()  // hereda los filtros activos de la tabla
            ->withFilename('visitas-' . now()->format('Y-m-d'))
    ])
```

Si se usa `Maatwebsite` directamente en vez de Filament's `ExportAction`:

```php
// app/Filament/Exports/VisitExporter.php
class VisitExporter extends Exporter
{
    protected static ?string $model = Visit::class;

    public static function modifyQuery(Builder $query): Builder
    {
        // El query ya llega con scopes y filtros aplicados desde el Resource
        return $query->with(['station.country', 'visitor']);
    }

    public static function getColumns(): array { /* ... */ }
}
```

---

## FASE 6 — Dashboard Widgets
**Duración estimada:** 3–4 h  
**Dependencias:** Fase 5 completa

| Widget | Tipo Filament | Polling | Orden |
|--------|---------------|---------|-------|
| `StatsOverviewWidget` | `StatsOverviewWidget` | 30s | 1° |
| `ActiveVisitsTableWidget` | `TableWidget` | 30s | 2° |
| `VisitsChartWidget` | `ChartWidget` | Sin polling | 3° |
| `MapWidget` | `Widget` (View custom) | Sin polling | 4° |

Todos los widgets aplican el mismo scope de país:
```php
$base = $user->isSuperAdmin()
    ? Visit::query()
    : Visit::whereHas('station', fn($q) => $q->where('country_id', $user->country_id));
```

### MapWidget — Implementación Leaflet Custom

**Archivos a crear:**
- `app/Filament/Widgets/MapWidget.php`
- `resources/views/filament/widgets/map-widget.blade.php`

**Componente PHP:**
```php
class MapWidget extends Widget
{
    protected static string $view = 'filament.widgets.map-widget';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 4;

    public function getViewData(): array
    {
        $user = auth()->user();
        $stations = Station::query()
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('country_id', $user->country_id))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn($s) => [
                'lat'    => (float) $s->latitude,
                'lng'    => (float) $s->longitude,
                'name'   => $s->name,
                'code'   => $s->code,
                'status' => match(true) {
                    $s->is_active && $s->is_registered => 'active',
                    $s->is_active                       => 'no-tablet',
                    default                             => 'inactive',
                },
            ]);

        return ['stations' => $stations->toArray()];
    }
}
```

**Vista Blade (patrón Alpine.js + Leaflet CDN):**
```html
<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="mapWidget(@json($stations))"
            x-init="init()"
        >
            <div id="station-map" style="height: 450px; width: 100%; z-index: 1;"></div>
        </div>
    </x-filament::section>

    @push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9/dist/leaflet.js"></script>
    <script>
    function mapWidget(stations) {
        return {
            init() {
                const map = L.map('station-map').setView([14.5, -89.5], 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                const colors = { active: 'green', 'no-tablet': 'orange', inactive: 'red' };
                stations.forEach(s => {
                    L.circleMarker([s.lat, s.lng], {
                        color: colors[s.status] ?? 'gray',
                        radius: 8,
                        fillOpacity: 0.8,
                    })
                    .bindPopup(`<b>${s.name}</b> (${s.code})<br>Estado: ${s.status}`)
                    .addTo(map);
                });
            }
        }
    }
    </script>
    @endpush
</x-filament-widgets::widget>
```

> `@push('scripts')` requiere que el layout de Filament incluya `@stack('scripts')`. Verificar en la vista del panel base o usar el hook `$panel->renderHook()` si no está disponible.

**Colores de marcadores:**
- `active` (activa + tablet registrada) → verde
- `no-tablet` (activa sin tablet) → naranja
- `inactive` → rojo

---

## FASE 7 — QA y Deploy
**Duración estimada:** 1–2 h  
**Dependencias:** Fase 6 completa

| # | Tarea | Cómo verificar |
|---|-------|----------------|
| 7.1 | `.env` no está en git | `git status` — no debe aparecer |
| 7.2 | `ADMIN_PANEL_LLD.md` no está en git | `git status` — no debe aparecer |
| 7.3 | Login con `super_admin` | Ve todos los países, todos los datos |
| 7.4 | Login con `country_manager` | Solo ve datos de su país; no ve menú Sistema |
| 7.5 | Login con `viewer` | Solo lectura; botones de crear/editar ocultos |
| 7.6 | Export CSV/Excel de visitas | Descarga con filtros activos aplicados |
| 7.7 | Polling de widgets (30s) | DevTools Network — requests cada 30s |
| 7.8 | MapWidget carga marcadores | Al menos una estación con lat/lng aparece |
| 7.9 | Cambiar password del super_admin | Panel > Usuarios > editar |
| 7.10 | `web.config` en IIS | Verificar que `portal.efltrackingsystem.com/` responde |

---

## Árbol de Dependencias

```
FASE 1 — Bootstrap
    └── FASE 2 — Modelos + Seeder
            └── FASE 3 — Panel Config
                    └── FASE 4 — Gates + Trait Scope
                            └── FASE 5 — Resources
                                    ├── 5.1 CountryResource
                                    ├── 5.2 UserResource
                                    ├── 5.3 VisitorResource
                                    ├── 5.4 StationResource
                                    └── 5.5 VisitResource + Export
                                                └── FASE 6 — Widgets
                                                        ├── StatsOverview   (30s polling)
                                                        ├── ActiveVisits    (30s polling)
                                                        ├── VisitsChart
                                                        └── MapWidget       (Leaflet custom)
                                                                └── FASE 7 — QA + Deploy
```

---

## Estimación Total

| Fase | Estimado | Estado |
|------|----------|--------|
| 1 — Bootstrap | ~~1–2 h~~ **~2 h real** | ✅ Completada |
| 2 — Modelos + Seeder | 1.5–2 h | Pendiente |
| 3 — Panel Config | 0.5 h | Pendiente |
| 4 — Gates + Trait | 0.75 h | Pendiente |
| 5 — Resources (5 items) | 6–8 h | Pendiente |
| 6 — Widgets (4 items) | 3–4 h | Pendiente |
| 7 — QA + Deploy | 1–2 h | Pendiente |
| **Total restante** | **~12–17 h** | |

---

## Referencia rápida para retomar sesión

```bash
# Estado de la implementación
php artisan about                           # verifica conexión DB y config
php artisan route:list --path=/             # resources Filament registrados

# Login de prueba
# http://localhost  →  admin@efltrackingsystem.com  /  EFL@Admin2026!
```

El checkpoint de cada fase está definido arriba. Al retomar, identificar en qué fase se está y continuar desde ahí.

---

*EFL Access Portal — Execution Plan · May 2026 · Revisión post-análisis R1–R6*
