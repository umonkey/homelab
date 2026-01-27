# Home Server Automation

This repository contains files needed to run all software on my home server.
It is intended to require minimal setup and configuration.
The idea is that everything runs under Docker Compose, with updates being deployed using a GitHub Actions workflow.


## TODO

- [ ] Move or drop OpenVPN
- [ ] Move land.umonkey.net
- [ ] Move seb-museum.ru
- [ ] Move sebezh-gid.ru
- [ ] Move chistoe-nebo.info
- [ ] Move Trees of Yerevan backups


## Configuring the server

This whole thing runs under Docker Compose.
Perform the following steps to set it up:

1. Create a server (a droplet on Digital Ocean, etc).
2. Create a separate user to run docker (e.g., "homelab").
3. Pull this repository to that user's home folder.
4. Generate an SSH key which will be used to deploy updates.


## Configuration

Most configuration happens via the environment, in the `compose.yaml` file.
