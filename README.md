# Home Server Automation

This repository contains files needed to run all software on my home server.
It is intended to require minimal setup and configuration.
The idea is that everything runs under Docker Compose, with updates being deployed using a GitHub Actions workflow.


## Configuring the server

This whole thing runs under Docker Compose.
Perform the following steps to set it up:

1. Create a server (a droplet on Digital Ocean, etc).
2. Create a separate user to run docker (e.g., "homelab"). Make sure it is added to the `docker` group to be able to run Docker.
3. Pull this repository to that user's home folder.
4. Generate an SSH key which will be used to deploy updates.


### Requirements

Make sure that `docker compose` is installed.

### Generating the SSH key

Use this command to generate a pair of SSH keys.
The public key goes to the server, `~/.ssh/authorized_keys`.
The private key goes to GitHub, a repo secret named `SSH_PRIVATE_KEY`.

```
ssh-keygen -t ed25519 -C "github-ci" -f ./deploy_key
```

Use this command to generate another pair of SSH keys, that will be used by the server to pull updates from the repo.
The public key goes to the "Deploy keys" section in GitHub, and the private key stays in `~/.ssh`.

```
ssh-keygen -t ed25519 -C "github-ci" -f ./deploy_key
```


## Configuration

Most configuration happens via the environment, in the `compose.yaml` file.
Secrets are passed via the standard secrets mechanism, aka files `/run/secrets/*` which containers can use as they need.

## Container images

Containers are built and stored in GHCR, a list can be viewed [here](https://github.com/umonkey?tab=packages).
To push images, you need a "personal access token" which needs to be set in the `GHCR_TOKEN` variable, and can be issued [here](https://github.com/settings/tokens).
