# Adding services

To add a new service, the following steps must be folowed.

1. Create a folder under `services`, like `services/foobar`, with a Dockerfile in the root.
2. Set up the service in `compose.yaml`. It is best to pull the image from GHCR.
3. Add the Docker image build and push job to `.github/workflows/deploy.yml`.
4. Update the "changes" step in `deploy.yml` to output a filter for the new service.
5. Update the `deploy` job to use that new fitler and depend on the new service build.
