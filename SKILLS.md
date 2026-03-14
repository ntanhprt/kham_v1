# SKILLS — Project Skill Index

This file lists all available skill files for Claude agents working on this codebase.
Load the relevant skill file at the start of any coding session.

---

## Available Skills

### `skills/php-yhct-web.md`

**PHP YHCT Web — Project Architecture & Conventions**

Load this skill for any development work on this Traditional Vietnamese Medicine (YHCT) diagnosis web application.

Covers:
- Project overview and purpose
- Full tech stack (PHP 8, SQLite, Bootstrap 5, Vanilla JS)
- Complete URL routing structure
- Full directory tree with file descriptions
- MVC conventions (App.php router, Controller/Model/View patterns)
- Database access patterns via BaseModel and Database::get() PDO
- CSRF protection requirements
- Flash message and redirect helpers
- Auth:: static methods for login and role checks
- Knowledge base schema (K01–K08 tables) with column descriptions
- Engine pipeline: Phase 0 (context) → Phase 1 (symptoms) → Phase 2 (questions) → Phase 3 (analysis)
- RelevanceScore formula with weights
- Bát Cương eight-principles axes (Yin/Yang, Interior/Exterior, Cold/Heat, Deficiency/Excess)
- YHCT organ systems with colors and CSS classes
- Safety rules (L1 emergency, L2 urgent, L3 watch)
- Common gotchas (JSON fields in SQLite, session_id UUID vs PHP session, suppress_yhct flag, etc.)
- New feature checklist

---

## How to Use

At the start of a session working on this project, tell Claude:

> "Load the skill file at skills/php-yhct-web.md before making any changes."

Or reference it directly in your prompt:

> "Using the conventions in skills/php-yhct-web.md, add a new endpoint to ExamController..."
