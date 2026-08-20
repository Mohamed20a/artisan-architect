<h1 align="center">🏗️ Artisan Architect</h1>

<p align="center">
  <strong>A powerful AST-based Laravel architecture analyzer that detects design violations, calculates health scores, generates visual reports, and integrates seamlessly with your CI/CD pipeline.</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/mohamed-elgeady/artisan-architect"><img src="https://img.shields.io/packagist/v/mohamed-elgeady/artisan-architect.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/mohamed-elgeady/artisan-architect"><img src="https://img.shields.io/packagist/dt/mohamed-elgeady/artisan-architect.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/mohamed-elgeady/artisan-architect"><img src="https://img.shields.io/packagist/php-v/mohamed-elgeady/artisan-architect.svg?style=flat-square" alt="PHP from Packagist"></a>
  <a href="https://github.com/Mohamed20a/artisan-architect/blob/main/LICENSE.md"><img src="https://img.shields.io/packagist/l/mohamed-elgeady/artisan-architect.svg?style=flat-square" alt="License"></a>
</p>

---

## 📖 Table of Contents

- [Introduction](#-introduction)
- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Available Commands](#-available-commands)
- [Example Output](#-example-output)
- [Generating Reports](#-generating-reports)
- [Generating Architecture Graphs](#-generating-architecture-graphs)
- [CI/CD Integration](#-cicd-integration)
- [Laravel Modules & DDD Support](#-laravel-modules--ddd-support)
- [Configuration Reference](#-configuration-reference)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🚀 Introduction

**Artisan Architect** is a zero-runtime, static code analysis package built specifically for the Laravel ecosystem. It leverages PHP's Abstract Syntax Tree (AST) via `nikic/php-parser` to deeply inspect your application's source code without executing a single line of it.

It scans your **Controllers**, **Models**, **Form Requests**, **API Resources**, and **Routes**, evaluates them against a configurable set of architectural rules, assigns a **health score (0–100)** to each class, and aggregates a project-wide score with a full breakdown of violations.

Think of it as **a code reviewer that never sleeps** — integrated directly into your Artisan command suite.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🧠 **AST-Powered Analysis** | Deep, accurate inspection of PHP source files — no runtime needed |
| 📊 **Health Scoring** | Every class gets a score (0–100). The overall project score is always visible |
| ❌ **Violation Detection** | Detects Fat Controllers, Direct DB Calls, Missing `$fillable`, Bloated Models, Closure Routes, and more |
| 🎨 **Beautiful Console Output** | Rich, color-coded, icon-decorated terminal output with detailed summaries |
| 🌐 **HTML Reports** | Stunning dark-mode HTML reports with Tailwind CSS glassmorphism design |
| 🗃️ **JSON Reports** | Machine-readable JSON reports for use with custom dashboards or APIs |
| 🔗 **Dependency Graphs** | Auto-generates interactive Mermaid.js class diagrams showing class relationships |
| 🧩 **Module Support** | Native wildcard (`*`) glob support for `nwidart/laravel-modules` and DDD structures |
| 🤖 **CI/CD Ready** | `--min-score` and `--fail-on` flags for enforcing quality gates in pipelines |
| 🎯 **Targeted Analysis** | Analyze the entire project, a specific directory, or a single file |
| 🔧 **Fully Configurable** | Tune every rule threshold (max methods, max lines, penalties, etc.) in the config |
| 🗂️ **Safe Storage** | All generated reports are safely stored in `storage/artisan-architect/` (not in `public/`) |

---

## 🔧 Requirements

| Dependency | Version |
|---|---|
| **PHP** | `^8.2` or `^8.3` |
| **Laravel** | `^10.0` / `^11.0` / `^12.0` / `^13.0` |
| **nikic/php-parser** | `^5.0` |

> The package should be installed as a **dev dependency** (`--dev`), as it is a developer tool and should not run in production.

---

## 📦 Installation

**Step 1 — Install via Composer:**

```bash
composer require mohamed-elgeady/artisan-architect --dev
```

**Step 2 — Publish the configuration file:**

```bash
php artisan vendor:publish --tag=artisan-architect-config
```

This will create `config/artisan-architect.php` in your application where you can configure paths, rules, scoring weights, and output settings.

---

## 🛠️ Available Commands

### `architect:analyze` — Project Architecture Analysis

```bash
# Analyze the full project with a rich console summary (default)
php artisan architect:analyze

# Show a highly detailed, file-by-file breakdown
php artisan architect:analyze --details

# Analyze only specific component types
php artisan architect:analyze --only=controllers,models

# Analyze a specific directory
php artisan architect:analyze app/Http/Controllers/Api

# Analyze a single file
php artisan architect:analyze app/Http/Controllers/UserController.php

# Generate a beautiful HTML report (saved to storage/artisan-architect/reports/)
php artisan architect:analyze --format=html

# Generate a JSON report
php artisan architect:analyze --format=json

# Save with a custom filename
php artisan architect:analyze --format=html --output=my-report.html

# Fail if the overall score is below a threshold (for CI/CD)
php artisan architect:analyze --min-score=85

# Fail if any critical errors are found
php artisan architect:analyze --fail-on=error

# Fail if any warnings or errors are found
php artisan architect:analyze --fail-on=warning
```

---

### `architect:graph` — Interactive Architecture Graph

```bash
# Generate an interactive HTML graph (saved to storage/artisan-architect/graphs/)
php artisan architect:graph

# Output raw Mermaid syntax (great for GitHub Markdown or Notion)
php artisan architect:graph --format=mermaid

# Output JSON with nodes and edges (for custom visualizations)
php artisan architect:graph --format=json
```

---

## 📸 Example Output

### Console Analysis (`architect:analyze`)

```
🏗️  Artisan Architect — Architecture Analysis
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎮 Controllers (10 files analyzed)

  AdminController AdminController.php   [75/100]
      └ Lines: 276  │  Methods: 12
    ❌ Controller AdminController has 276 lines. Maximum recommended is 200.
    ⚠️  Controller AdminController has 12 methods. Maximum recommended is 10.

  UserController UserController.php   [55/100]
      └ Lines: 270  │  Methods: 11  │  DB calls: 1
    ❌ Controller UserController has 270 lines. Maximum recommended is 200.
    ⚠️  Controller UserController has 11 methods. Maximum recommended is 10.
    ❌ Controller UserController contains a direct database call (DB::transaction). Move query logic to a repository or service. :42

  VendorController VendorController.php   [15/100]
      └ Lines: 373  │  Methods: 12  │  DB calls: 3
    ❌ Controller VendorController has 373 lines. Maximum recommended is 200.
    ⚠️  Controller VendorController has 12 methods. Maximum recommended is 10.
    ❌ Controller VendorController contains a direct database call (DB::beginTransaction). :51
    ❌ Controller VendorController contains a direct database call (DB::commit). :84
    ❌ Controller VendorController contains a direct database call (DB::rollBack). :96

  CategoryController CategoryController.php   [100/100] 🟢
      └ Lines: 97  │  Methods: 6

  OrderController OrderController.php   [100/100] 🟢
      └ Lines: 107  │  Methods: 7

🗂️ Models (8 files analyzed)

  User User.php   [100/100] 🟢
      └ Lines: 92  │  Methods: 7  │  Relations: 3

  Product Product.php   [100/100] 🟢
      └ Lines: 38  │  Methods: 5  │  Relations: 5

  Vendor Vendor.php   [100/100] 🟢
      └ Lines: 49  │  Methods: 6  │  Relations: 4

📋 Requests (6 files analyzed)

  LoginRequest LoginRequest.php   [100/100] 🟢
      └ Lines: 85  │  Methods: 5

  RegisterVendorsRequest RegisterVendorsRequest.php   [100/100] 🟢
      └ Lines: 90  │  Methods: 3

📦 Resources (8 files analyzed)

  UserResource UserResource.php   [100/100] 🟢
      └ Lines: 34

  VendorResource VendorResource.php   [100/100] 🟢
      └ Lines: 42

🛣️ Routes (4 files analyzed)

  RouteFile: api.php api.php   [100/100] 🟢
      └ Lines: 18

  RouteFile: auth.php auth.php   [100/100] 🟢
      └ Lines: 58

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📈 Summary
  Files Analyzed : 36
  Errors         : 13
  Warnings       : 3
  Overall Score  : 92/100  🟢 Excellent
```

---

### HTML Report (`--format=html`)

The HTML report is a beautiful dark-mode, interactive dashboard with glassmorphism UI. It displays:
- Overall score with color-coded rating
- Per-class health cards with all violations listed
- File paths, line counts, and metrics

Saved automatically to `storage/artisan-architect/reports/architecture.html`.

---

### Architecture Graph (`architect:graph`)

```bash
php artisan architect:graph
# → Graph exported successfully to: storage/artisan-architect/graphs/architecture.html
```

Open the file in your browser to see an **interactive Mermaid.js class diagram** showing all class dependencies in your project. The graph is rendered client-side in dark mode with full SVG quality.

---

## 🤖 CI/CD Integration

Add Artisan Architect to your **GitHub Actions** workflow to enforce architectural quality on every pull request:

```yaml
# .github/workflows/architecture.yml
name: Architecture Analysis

on: [push, pull_request]

jobs:
  analyze:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Architecture Analysis
        run: php artisan architect:analyze --min-score=80 --fail-on=error
```

> If the overall project score drops below **80** or any critical errors are found, the workflow will fail and block the merge. 🚫

---

## 🧩 Laravel Modules & DDD Support

Working with `nwidart/laravel-modules` or a Domain-Driven Design structure? Artisan Architect has **native wildcard support**.

Open `config/artisan-architect.php` and add your module paths alongside the standard paths:

```php
'paths' => [
    'controllers' => [
        base_path('app/Http/Controllers'),
        base_path('Modules/*/Http/Controllers'), // ✅ Scans ALL modules automatically
    ],
    'models' => [
        base_path('app/Models'),
        base_path('Modules/*/Models'),
    ],
    'requests' => [
        base_path('app/Http/Requests'),
        base_path('Modules/*/Http/Requests'),
    ],
    'resources' => [
        base_path('app/Http/Resources'),
        base_path('Modules/*/Http/Resources'),
    ],
    'routes' => [
        base_path('routes'),
        base_path('Modules/*/routes'),
    ],
],
```

The `*` wildcard will automatically expand to match every module directory under `Modules/`, giving you a **single unified report** for your entire application. ✅

---

## ⚙️ Configuration Reference

The published config file gives you full control over the analyzer's behavior:

```php
// config/artisan-architect.php

return [

    // ─────────────────────────────────────────
    // Paths to scan. Supports strings AND arrays
    // with glob wildcards (*) for Module support.
    // ─────────────────────────────────────────
    'paths' => [
        'controllers' => [
            base_path('app/Http/Controllers'),
            // base_path('Modules/*/Http/Controllers'),
        ],
        'models' => [
            base_path('app/Models'),
        ],
        // ... routes, requests, resources, services
    ],

    // ─────────────────────────────────────────
    // Rule thresholds — tune to your standards.
    // ─────────────────────────────────────────
    'rules' => [
        'controllers' => [
            'max_methods'           => 10,
            'max_lines'             => 200,
            'allow_db_queries'      => false, // penalize direct DB facade usage
            'require_form_requests' => true,
        ],
        'models' => [
            'max_methods'       => 30,
            'max_lines'         => 300,
            'require_fillable'  => true,
            'max_relationships' => 15,
        ],
    ],

    // ─────────────────────────────────────────
    // Health score penalty weights per severity.
    // ─────────────────────────────────────────
    'score' => [
        'error_penalty'   => 20,
        'warning_penalty' => 5,
        'info_penalty'    => 1,
    ],

    // ─────────────────────────────────────────
    // Output & report storage configuration.
    // Reports are stored in storage/ by default.
    // ─────────────────────────────────────────
    'output' => [
        'format'            => 'console',
        'directory'         => storage_path('artisan-architect'),
        'reports_directory' => 'reports',
        'graphs_directory'  => 'graphs',
    ],

];
```

---

## 🤝 Contributing

Contributions are welcome and appreciated! If you have an idea for a new architectural rule or a feature:

1. Fork the repository.
2. Create your feature branch: `git checkout -b feature/my-new-rule`.
3. Implement your rule inside `src/Rules/`.
4. Add a test inside `tests/`.
5. Submit a Pull Request.

---

## 🛡️ Security

If you discover any security-related issues, please email [m16ahmed17@gmail.com](mailto:m16ahmed17@gmail.com) instead of using the issue tracker.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<p align="center">
  Built with ❤️ for the Laravel Community by <a href="https://www.linkedin.com/in/mohamed-ahmed-bb358b239"><strong>Mohamed Elgeady</strong></a>
</p>
