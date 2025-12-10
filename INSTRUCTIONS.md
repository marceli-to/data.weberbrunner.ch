# Application Architecture - data.weberbrunner.ch

This document describes the architecture and organization of the data.weberbrunner.ch application for managing architecture project data.

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.3+)
- **Frontend:** Vue 3 with Composition API (`<script setup>`)
- **Routing:** Vue Router 4 (client-side SPA routing)
- **Styling:** Tailwind CSS 4
- **Icons:** Phosphor Icons (`@phosphor-icons/vue`)
- **Build Tool:** Vite with `laravel-vite-plugin`
- **HTTP Client:** Axios
- **Drag & Drop:** vuedraggable

---

## Data Source

Project data is imported from JSON files located in:
```
storage/app/public/import/projects/*.json
```

Each JSON file contains a complete project export from WordPress with:
- Project metadata (title, slug, year, status)
- Steckbrief (master data text block)
- Content blocks (text and media)
- Image attachments with metadata
- Category assignments

---

## Database Schema

### Entity Relationship

```
┌─────────────┐       ┌──────────────────┐
│  projects   │───┬───│  project_texts   │
└─────────────┘   │   └──────────────────┘
       │          │
       │          ├───│  project_images  │
       │          │   └──────────────────┘
       │          │
       │          └───│  project_data    │
       │              └──────────────────┘
       │
       └──────────────│  categories      │ (many-to-many)
                      └──────────────────┘
```

### projects

Main project table storing core project information.

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('wp_id')->nullable()->unique();  // Original WordPress ID
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('year', 4)->nullable();
    $table->string('status')->nullable();                       // Studie, Realisiert, Wettbewerb, etc.
    $table->text('steckbrief')->nullable();                     // Master data text block
    $table->string('publish_status')->default('publish');       // publish, draft
    $table->unsignedInteger('menu_order')->default(0);
    $table->string('color_text_dark', 7)->nullable();           // Hex color e.g. #ffffff
    $table->string('color_text_light', 7)->nullable();          // Hex color e.g. #000000
    $table->timestamps();
    $table->softDeletes();
});
```

**JSON Mapping:**
| JSON Field | Database Column |
|------------|-----------------|
| `id` | `wp_id` |
| `title` | `title` |
| `slug` | `slug` |
| `meta.year` | `year` |
| `meta.status` | `status` |
| `meta.steckbrief` | `steckbrief` |
| `status` | `publish_status` |
| `menu_order` | `menu_order` |
| `meta.color_for_black_text` | `color_text_dark` |
| `meta.color_for_white_text` | `color_text_light` |

---

### project_texts

Content blocks - text elements that appear in the project layout.

```php
Schema::create('project_texts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('type');                    // text, text_large
    $table->text('text')->nullable();
    $table->string('custom_css')->nullable();  // Positioning CSS
    $table->unsignedInteger('position')->default(0);
    $table->timestamps();
});
```

**JSON Mapping (from `content_blocks` where type is text/text_large):**
| JSON Field | Database Column |
|------------|-----------------|
| `content_blocks[].type` | `type` |
| `content_blocks[].text` | `text` |
| `content_blocks[].custom_css` | `custom_css` |
| (array index) | `position` |

---

### project_images

All images associated with a project - both from content blocks and attachments.

```php
Schema::create('project_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->unsignedBigInteger('wp_id')->nullable();  // Original WordPress attachment ID
    $table->string('filename');
    $table->string('title')->nullable();
    $table->string('alt')->nullable();
    $table->text('caption')->nullable();
    $table->text('description')->nullable();
    $table->string('mime_type')->nullable();
    $table->unsignedInteger('width')->nullable();
    $table->unsignedInteger('height')->nullable();
    $table->json('sizes')->nullable();                // Thumbnail, medium, large variants
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_content_block')->default(false);
    $table->string('content_block_css')->nullable();  // CSS if used in content block
    $table->string('content_block_caption')->nullable();
    $table->unsignedInteger('position')->default(0);
    $table->timestamps();
});
```

**JSON Mapping (from `attachments` and `content_blocks` where type is media):**

From `attachments[]`:
| JSON Field | Database Column |
|------------|-----------------|
| `attachments[].id` | `wp_id` |
| `attachments[].filename` | `filename` |
| `attachments[].title` | `title` |
| `attachments[].alt` | `alt` |
| `attachments[].caption` | `caption` |
| `attachments[].description` | `description` |
| `attachments[].mime_type` | `mime_type` |
| `attachments[].metadata.width` | `width` |
| `attachments[].metadata.height` | `height` |
| `attachments[].metadata.sizes` | `sizes` (JSON) |

From `meta.featured_image`:
| JSON Field | Database Column |
|------------|-----------------|
| `meta.featured_image` == attachment id | `is_featured` = true |

From `content_blocks[]` where type is media:
| JSON Field | Database Column |
|------------|-----------------|
| `content_blocks[].image` | matches `wp_id`, sets `is_content_block` = true |
| `content_blocks[].custom_css` | `content_block_css` |
| `content_blocks[].text` | `content_block_caption` |

---

### project_data

Key-value store for additional metadata. Useful for storing related_projects and other meta fields.

```php
Schema::create('project_data', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('key');
    $table->text('value')->nullable();
    $table->timestamps();

    $table->unique(['project_id', 'key']);
});
```

**JSON Mapping (from `meta`):**
| JSON Field | Database Key |
|------------|--------------|
| `meta.project_notes` | `project_notes` |
| `meta.related_projects` | `related_projects` (JSON array as string) |
| `meta.has_featured_video` | `has_featured_video` |

---

### categories

Filter categories for projects.

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->timestamps();
    $table->softDeletes();
});
```

---

### category_project (pivot)

```php
Schema::create('category_project', function (Blueprint $table) {
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->primary(['project_id', 'category_id']);
});
```

**JSON Mapping (from `categories[]`):**
| JSON Field | Database Column |
|------------|-----------------|
| `categories[].name` | `categories.name` |
| `categories[].slug` | `categories.slug` |

---

## Models

### Project Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wp_id',
        'title',
        'slug',
        'year',
        'status',
        'steckbrief',
        'publish_status',
        'menu_order',
        'color_text_dark',
        'color_text_light',
    ];

    public function texts(): HasMany
    {
        return $this->hasMany(ProjectText::class)->orderBy('position');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('position');
    }

    public function featuredImage(): HasOne
    {
        return $this->hasOne(ProjectImage::class)->where('is_featured', true);
    }

    public function contentBlockImages(): HasMany
    {
        return $this->hasMany(ProjectImage::class)
            ->where('is_content_block', true)
            ->orderBy('position');
    }

    public function data(): HasMany
    {
        return $this->hasMany(ProjectData::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    // Helper to get a specific data value
    public function getDataValue(string $key): ?string
    {
        return $this->data->where('key', $key)->first()?->value;
    }
}
```

### ProjectText Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectText extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'text',
        'custom_css',
        'position',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

### ProjectImage Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    protected $fillable = [
        'project_id',
        'wp_id',
        'filename',
        'title',
        'alt',
        'caption',
        'description',
        'mime_type',
        'width',
        'height',
        'sizes',
        'is_featured',
        'is_content_block',
        'content_block_css',
        'content_block_caption',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'sizes' => 'array',
            'is_featured' => 'boolean',
            'is_content_block' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Get URL for a specific size
    public function getUrl(string $size = 'large'): ?string
    {
        if ($this->sizes && isset($this->sizes[$size]['file'])) {
            return '/storage/images/' . $this->sizes[$size]['file'];
        }
        return '/storage/images/' . $this->filename;
    }
}
```

### ProjectData Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectData extends Model
{
    protected $fillable = [
        'project_id',
        'key',
        'value',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
```

### Category Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}
```

---

## API Controllers

### ProjectController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = Project::with(['featuredImage', 'categories'])
            ->withCount(['texts', 'images'])
            ->orderBy('menu_order')
            ->get();

        return response()->json($projects);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load([
            'texts',
            'images',
            'featuredImage',
            'contentBlockImages',
            'data',
            'categories',
        ]);

        return response()->json($project);
    }

    public function update(Project $project): JsonResponse
    {
        $validated = request()->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:projects,slug,' . $project->id,
            'year' => 'sometimes|nullable|string|max:4',
            'status' => 'sometimes|nullable|string|max:255',
            'steckbrief' => 'sometimes|nullable|string',
            'publish_status' => 'sometimes|string|in:publish,draft',
            'menu_order' => 'sometimes|integer',
            'color_text_dark' => 'sometimes|nullable|string|max:7',
            'color_text_light' => 'sometimes|nullable|string|max:7',
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    // Nested: Texts
    public function texts(Project $project): JsonResponse
    {
        return response()->json($project->texts);
    }

    public function updateText(Project $project, ProjectText $text): JsonResponse
    {
        $validated = request()->validate([
            'type' => 'sometimes|string|in:text,text_large',
            'text' => 'sometimes|nullable|string',
            'custom_css' => 'sometimes|nullable|string',
            'position' => 'sometimes|integer',
        ]);

        $text->update($validated);

        return response()->json($text);
    }

    // Nested: Images
    public function images(Project $project): JsonResponse
    {
        return response()->json($project->images);
    }

    public function updateImage(Project $project, ProjectImage $image): JsonResponse
    {
        $validated = request()->validate([
            'title' => 'sometimes|nullable|string|max:255',
            'alt' => 'sometimes|nullable|string|max:255',
            'caption' => 'sometimes|nullable|string',
            'is_featured' => 'sometimes|boolean',
            'position' => 'sometimes|integer',
        ]);

        // If setting as featured, unset other featured images
        if (isset($validated['is_featured']) && $validated['is_featured']) {
            $project->images()->where('id', '!=', $image->id)->update(['is_featured' => false]);
        }

        $image->update($validated);

        return response()->json($image);
    }
}
```

---

## API Routes

### routes/api.php

```php
<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // Project Texts
    Route::get('/projects/{project}/texts', [ProjectController::class, 'texts']);
    Route::put('/projects/{project}/texts/{text}', [ProjectController::class, 'updateText']);

    // Project Images
    Route::get('/projects/{project}/images', [ProjectController::class, 'images']);
    Route::put('/projects/{project}/images/{image}', [ProjectController::class, 'updateImage']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});
```

---

## Import Command

Create an artisan command to import JSON files:

```php
<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportProjects extends Command
{
    protected $signature = 'import:projects {--fresh : Delete all existing data first}';
    protected $description = 'Import projects from JSON files';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->info('Clearing existing data...');
            Project::query()->forceDelete();
            Category::query()->forceDelete();
        }

        $files = Storage::disk('public')->files('import/projects');
        $bar = $this->output->createProgressBar(count($files));

        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) continue;

            $json = json_decode(Storage::disk('public')->get($file), true);
            $this->importProject($json);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Import completed!');

        return Command::SUCCESS;
    }

    private function importProject(array $data): void
    {
        // Create or update project
        $project = Project::updateOrCreate(
            ['wp_id' => $data['id']],
            [
                'title' => $data['title'],
                'slug' => $data['slug'],
                'year' => $data['meta']['year'] ?? null,
                'status' => $data['meta']['status'] ?? null,
                'steckbrief' => $data['meta']['steckbrief'] ?? null,
                'publish_status' => $data['status'],
                'menu_order' => $data['menu_order'] ?? 0,
                'color_text_dark' => $data['meta']['color_for_black_text'] ?? null,
                'color_text_light' => $data['meta']['color_for_white_text'] ?? null,
            ]
        );

        // Import categories
        $categoryIds = [];
        foreach ($data['categories'] ?? [] as $cat) {
            $category = Category::firstOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name']]
            );
            $categoryIds[] = $category->id;
        }
        $project->categories()->sync($categoryIds);

        // Import attachments as images
        $project->images()->delete();
        $featuredImageWpId = $data['meta']['featured_image'] ?? null;

        foreach ($data['attachments'] ?? [] as $index => $attachment) {
            $project->images()->create([
                'wp_id' => $attachment['id'],
                'filename' => $attachment['filename'],
                'title' => $attachment['title'] ?? null,
                'alt' => $attachment['alt'] ?? null,
                'caption' => $attachment['caption'] ?? null,
                'description' => $attachment['description'] ?? null,
                'mime_type' => $attachment['mime_type'] ?? null,
                'width' => $attachment['metadata']['width'] ?? null,
                'height' => $attachment['metadata']['height'] ?? null,
                'sizes' => $attachment['metadata']['sizes'] ?? null,
                'is_featured' => ($attachment['id'] == $featuredImageWpId),
                'position' => $index,
            ]);
        }

        // Import content blocks
        $project->texts()->delete();
        $textPosition = 0;

        foreach ($data['content_blocks'] ?? [] as $index => $block) {
            if (in_array($block['type'], ['text', 'text_large'])) {
                $project->texts()->create([
                    'type' => $block['type'],
                    'text' => $block['text'] ?? null,
                    'custom_css' => $block['custom_css'] ?? null,
                    'position' => $textPosition++,
                ]);
            }

            // Mark images used in content blocks
            if ($block['type'] === 'media' && !empty($block['image'])) {
                $project->images()
                    ->where('wp_id', $block['image'])
                    ->update([
                        'is_content_block' => true,
                        'content_block_css' => $block['custom_css'] ?? null,
                        'content_block_caption' => $block['text'] ?? null,
                        'position' => $index,
                    ]);
            }
        }

        // Import additional meta data
        $project->data()->delete();
        if (!empty($data['meta']['project_notes'])) {
            $project->data()->create(['key' => 'project_notes', 'value' => $data['meta']['project_notes']]);
        }
        if (!empty($data['meta']['related_projects'])) {
            $project->data()->create(['key' => 'related_projects', 'value' => json_encode($data['meta']['related_projects'])]);
        }
    }
}
```

---

## Vue Components Structure

```
resources/js/components/
├── Sidebar.vue              # Navigation sidebar
├── ToastNotification.vue    # Toast messages
│
├── projects/                # Project feature
│   ├── Table.vue           # List view with filters
│   └── Form.vue            # Edit form with texts, images, data
│
├── categories/              # Category feature
│   ├── Table.vue
│   └── Form.vue
│
└── lightbox/                # Modal/Dialog components
    ├── Filters.vue         # Filter panel (by category, status, year)
    ├── ImageEdit.vue       # Edit image metadata
    ├── TextEdit.vue        # Edit text block
    └── ImageGallery.vue    # Browse all project images
```

---

## Authentication System

### Overview
The application uses **Laravel's session-based authentication** (not token-based).

### Key Files

```
app/Http/Controllers/Auth/LoginController.php  # Handles login/logout
resources/views/auth/login.blade.php           # Login form (Blade)
routes/web.php                                 # Auth routes
```

### Routes (web.php)

```php
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/{any?}', function () {
        return view('app');
    })->where('any', '.*');
});
```

### LoginController Pattern

```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    throw ValidationException::withMessages([
        'email' => 'The provided credentials do not match our records.',
    ]);
}
```

---

## Blade Views

### app.blade.php (SPA Container)

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-y-scroll">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 h-full">
    <div id="app" class="flex flex-col min-h-screen"></div>
</body>
</html>
```

---

## Vue Router Configuration

### router.js

```javascript
import { createRouter, createWebHistory } from 'vue-router';
import ProjectsTable from './components/projects/Table.vue';
import ProjectsForm from './components/projects/Form.vue';
import CategoriesTable from './components/categories/Table.vue';
import CategoriesForm from './components/categories/Form.vue';

const routes = [
  { path: '/', redirect: '/projects' },
  { path: '/projects', name: 'projects.index', component: ProjectsTable },
  { path: '/projects/:id/edit', name: 'projects.edit', component: ProjectsForm },
  { path: '/categories', name: 'categories.index', component: CategoriesTable },
  { path: '/categories/:id/edit', name: 'categories.edit', component: CategoriesForm },
  { path: '/categories/create', name: 'categories.create', component: CategoriesForm },
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;
```

---

## Key Conventions

1. **German UI text** - All user-facing text is in German
2. **API responses** - Always return JSON, no redirects
3. **Soft deletes** - Models use `SoftDeletes` trait
4. **Eager loading** - Use `with()` and `withCount()` in index methods
5. **Form validation** - Validate in controller using `request()->validate()`
6. **Component composition** - Use `<script setup>` with Composition API
7. **Icons** - Use Phosphor Icons with `Ph` prefix
8. **Toast notifications** - Use ToastNotification component for feedback
9. **Lightboxes** - Use modal pattern with backdrop click to close

---

## Setup Commands

```bash
# Create migrations
php artisan make:migration create_projects_table
php artisan make:migration create_project_texts_table
php artisan make:migration create_project_images_table
php artisan make:migration create_project_data_table
php artisan make:migration create_categories_table
php artisan make:migration create_category_project_table

# Create models
php artisan make:model Project
php artisan make:model ProjectText
php artisan make:model ProjectImage
php artisan make:model ProjectData
php artisan make:model Category

# Create controllers
php artisan make:controller Api/ProjectController
php artisan make:controller Api/CategoryController

# Create import command
php artisan make:command ImportProjects

# Run migrations
php artisan migrate

# Run import
php artisan import:projects --fresh
```
