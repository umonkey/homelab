# Deployment

We use GitHub Actions to deploy things.
The Docker images are built on the GitHub side, then stored in GHCR.
When the images are ready, a local task runs in a self-hosted Actions runner, to trigger the updates.
This document describes how to configure all that.


## Running the services

The whole thing runs unside Docker Compose, as a set of services.
A user named `homelab` needs to be created.
The repository needs to be cloned as `~homelab/repo`.
The `update.sh` script in the root of the repo downloads and runs everything.


## Deployment steps

When anything is pushed to the master branch, the server updates using GitHub Actions.
First, all services have their Docker images built and uploaded to GHCR.
Then a job runs that triggers Docker Compose to pull updates and restart services as needed.


## Enabling the self-hosted runner

1. Go to the repo settings → Actions → Runners and enable the Linux runner.
2. Log in to the server, as user `homelab` which runs all the automation.
3. Execute the steps provided by GitHub, to have the agent running.
4. Make sure you install it as a service, or use `supervisor` to run it.
5. Use [this page](https://github.com/umonkey/homelab/settings/actions/runners) to make sure the runner is actually running.
