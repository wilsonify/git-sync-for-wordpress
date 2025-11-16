---
layout: default
title: Code Quality Gate Best Practices
---

# Maintaining Code Quality

This guide captures the habits that keep the Git Sync plugin green in SonarQube. 

Use it as a checklist whenever you touch the codebase or write new automation.

## 1. Treat Sonar issues as release blockers
- **Run the scanner after every significant change** (or at least before opening a PR). 
- Small issues are easiest to fix when they are fresh.
- **Keep the `sonar-project.properties` file accurate.** 
- When you add new source folders, tests, or coverage exclusions, 
- update the config in the same commit so Sonar never analyzes stale paths.
- **Resolve or triage every alert immediately.** If something must be deferred, 
- document the rationale in the issue tracker and add a suppression (with justification) 
- so the gate still passes.

## 2. Make coverage a non-negotiable
- Our quality gate requires **≥ 80% line coverage** on the code that is realistically unit-testable.
- **Run PHPUnit with coverage locally** before every scan:

```bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
```

- **Add tests where coverage is thin.** Favor small, deterministic unit tests against `GitSyncGitOperations`, `GitSyncMarkdownParser`, and utility classes. 
- When real WordPress hooks are required, isolate the logic behind adapters so that the pure PHP portions stay testable.
- **Use coverage exclusions sparingly.** Files such as `git-sync.php` or the WordPress admin glue are excluded because they require a full WP runtime. 
- If you add more exclusions, document why in the PR and consider building stubs instead.

## 3. Keep dependencies and tooling aligned
- **Composer dev dependencies** (PHPUnit, Xdebug) must stay installed in development containers and CI so coverage reports are always produced.
- **Lock SonarScanner settings** in automation scripts (see command below) and prefer the same Docker image locally and in CI to avoid version drift.
- **Pin any third-party coding standards** or linters you reference in pull requests so Sonar and local tooling agree on findings.

## 4. Run the exact scanner command
Execute Sonar from the plugin root so relative paths resolve correctly. 
The following Docker invocation mirrors our CI job:

```bash
cd wordpress/wp-content/plugins/git-sync-for-wordpress
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text

docker run --rm --network host \
  -e SONAR_HOST_URL=http://localhost:9000 \
  -e SONAR_TOKEN=<your-token> \
  -v "$(pwd)":/usr/src -w /usr/src \
  sonarsource/sonar-scanner-cli \
  sonar-scanner -Dsonar.projectKey=renewed-renaissance
```

Tips:
- Replace `<your-token>` with a token that has "Execute Analysis" scope.
- If you run the scanner outside Docker, ensure `sonar-scanner` is v7.3+ to match the server.

## 5. Watch for common regressions
- **New files missing `git blame`:** Run the scanner inside the git workspace (or mount `.git/` into Docker) so Sonar can attribute lines to commits. 
- Without blame data, some features (like new-code tracking) degrade.
- **Forgotten coverage reports:** If `build/logs/clover.xml` is missing, 
- Sonar will drop to 0% coverage. Always rerun PHPUnit after touching tests or configuration.
- **Changed WordPress integration points:** When hooks or AJAX endpoints move, 
- update both the production code and any stubs in `tests/bootstrap.php` so unit tests keep executing the same paths Sonar expects.

## 6. Automate whenever possible
- Wire the PHPUnit + Sonar scanner steps into your CI pipeline so every push re-validates quality gates.
- Fail the pipeline when Sonar’s Quality Gate is red, ensuring unreviewed regressions never land on main.
- Periodically sync the local SonarQube server with production settings (quality profiles, gates) so local dress rehearsals match what CI enforces.

Sticking to these practices keeps the plugin shippable, the gate permanently green, and Sonar findings actionable instead of noisy.
