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


## Configuration

Most configuration happens via the environment, in the `compose.yaml` file.
