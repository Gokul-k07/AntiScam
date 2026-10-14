---
inclusion: auto
---

# ScamShield — AI Agent Workflow

Follow this workflow for every coding session on this project.

## Mandatory sequence

### 1. Inspect before touching
- Run `git status` in `C:\xampp\htdocs\AntiScam\`.
- Read any files relevant to the task.
- Identify what exists, what is planned but not yet created, and what is unresolved.

### 2. Plan before implementing
- State which files will be created or modified and why.
- Identify any rule conflicts, open decisions, or missing prerequisites.
- Wait for approval on destructive, risky, or ambiguous actions.

### 3. Implement in small, coherent steps
- Follow the existing structure from `Docs/ARCHITECTURE.md` and `.kiro/steering/structure.md`.
- Follow naming conventions in `.kiro/steering/structure.md`.
- Follow all security rules in `.kiro/steering/security.md`.
- Do not add dependencies without explaining why.
- Do not put source code in `Docs/`.
- Reuse existing services and repositories — check `app/Services/` and `app/Repositories/` before creating new classes.

### 4. Test and report
- Run the most relevant tests after changes. Report exact commands and output.
- If tests cannot run, state why clearly.
- Summarize: files changed, behavior implemented, tests run, risks remaining.

### 5. Update documentation only for meaningful changes
- Update `Docs/TASKS.md`, `Docs/MEMORY.md`, or `Docs/DECISION.md` when a task is completed or a decision is made.
- Mark tasks complete only when there is evidence.
- Do not update docs for every trivial edit.
- Keep documentation consistent with implemented behavior. Mark planned features as planned.

## VirusTotal integration rules

- `VirusTotalProvider` is in `app/Services/Analysis/`. Do NOT add a second VT client.
- VT lookups happen in `AnalysisController::analyze()` after local heuristics, URL type only.
- SMS/UPI text is NEVER sent to VT. This is a hard rule, not a suggestion.
- The VT result is displayed separately from the heuristic score in `url_result.php`.
- "Not found" and "zero detections" MUST carry a "not evidence of safety" caveat in every view.
- The API key lives in `.env` only. It must not appear in logs, views, or any committed file.
- `ReportCache` stores cache in `storage/vt_cache/` using SHA-256 URL keys. Don't bypass it.

## Standing constraints
- Do not commit or push without explicit user instruction.
- Do not reset, delete, or overwrite unrelated files.
- Do not expose passwords, tokens, secrets, or stack traces.
- Do not invent successful test results.

Current project task board:

#[[file:Docs/TASKS.md]]

Open decisions:

#[[file:Docs/DECISION.md]]
