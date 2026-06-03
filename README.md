# Purple PHP

![Purple PHP hero graphic](hero.png)

Purple PHP is an enterprise AI modernization SDK for existing PHP applications.

It is designed for teams with significant sunk cost in PHP-based CMS, ecommerce, admin, support, catalog, and workflow systems. The goal is to add secure, auditable AI behavior without rewriting the application estate.

## Product Shape

Purple PHP is Composer-first. Native runtime, sidecar, and PHP extension support may become important deployment modes later, but the developer-facing center of gravity is PHP.

The long-horizon product has three capability layers:

1. Smart functions: typed, auditable AI calls for narrow repeatable tasks.
2. Chatbots: stateful assistants embedded into existing application surfaces.
3. Looping agents: goal-driven runners that use tools under policy, audit, and approval controls.

## First Milestone

The first shippable milestone is smart functions.

MVP 1 should prove:

* provider abstraction
* secure environment secret resolution
* typed inputs and structured outputs
* prompt templates
* schema validation
* retry and error handling
* basic policy checks
* audit logging
* fake provider support for tests
* OpenAI provider support

Chatbots, looping agents, runtime hooks, the CLI, native runtime, and domain adapters remain part of the roadmap, but they are deliberately outside the first milestone.

## Enterprise Principles

Purple PHP treats existing PHP estates as valuable terrain.

The SDK should be:

* Composer-first
* provider-neutral
* secure by default
* auditable by default
* policy-driven
* CMS-agnostic
* ecommerce-aware
* optional-native, not native-required

Policy is enforcement. Hooks are extension points.

## Planning Artifacts

The current project outline lives in [outline.md](outline.md).

The Spec Kitty mission package lives in [kitty-specs/001-purple-php-enterprise-ai-sdk](kitty-specs/001-purple-php-enterprise-ai-sdk/spec.md).

