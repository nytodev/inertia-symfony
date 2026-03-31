---
name: plan
description: Plan the implementation of a new Inertia bundle feature before writing any code. Creates a detailed implementation plan.
argument-hint: <feature-description>
---

# Implementation Plan — symfony-inertia-bundle

## Feature to Plan
$ARGUMENTS

## Planning Steps

### 1. Read the protocol spec
Load `.claude/skills/inertia-protocol/SKILL.md` and identify which protocol behaviors are involved.

### 2. Check the Laravel reference implementation
Read `/home/tony/Documents/tony/inertia-laravel` to see how the same feature is handled in the Laravel bundle. Use it as a reference for expected behavior and conventions.

### 3. Read existing code
Explore relevant files in `src/` to understand the current state.

### 4. Write the plan

The plan must include:

**Files to create** (with their purpose):
```
src/NewClass.php       — Description of responsibility
tests/Unit/...         — Unit tests for NewClass
tests/Functional/...   — Protocol compliance tests
```

**Files to modify** (with what changes):
```
src/ExistingClass.php  — Add method X, modify method Y
config/services.yaml   — Register new service
config/definition.php  — Add new config option if needed
```

**Implementation order** (TDD sequence):
1. Write test for behavior A → implement A
2. Write test for behavior B → implement B
3. ...

**Protocol compliance checklist**:
- Which protocol behaviors are covered?
- Which edge cases need tests?

**Risks / open questions**:
- Any ambiguity in the spec?
- Symfony version compatibility concerns?

### 5. Present the plan for review
Do NOT start implementing. Present the plan and wait for approval.
If the plan is approved, use `/tdd` to implement step by step.