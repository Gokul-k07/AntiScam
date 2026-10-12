---
inclusion: auto
---

# ScamShield — Product Context

ScamShield is a web-based digital-safety assistant (PHP/MySQL, local XAMPP). It helps users assess suspicious URLs and messages, organize incident evidence, and find official reporting routes. It does not determine guilt or replace law enforcement.

Full product requirements, goals, non-goals, user roles, MVP functional requirements, and open decisions:

#[[file:Docs/PRD.md]]

## Key constraints for AI agents
- Never claim a URL or person is confirmed safe or criminal based on heuristic score alone.
- Every risk result must show the triggered indicators and their limitations.
- The platform distinguishes its own case reference from an official police complaint.
- Do not guarantee detection, money recovery, or law-enforcement action.
- Do not label "no threat-feed match" as "safe."
