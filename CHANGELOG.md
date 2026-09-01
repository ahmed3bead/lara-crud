# Changelog

## v0.8.0

### Removed

- **Test stub generation.** The `lara-crud:unit-test` command and the test-generation prompt in `lara-crud:go` have been removed. The emitted stubs contained placeholder assertions, unfilled factory data, and incorrect route names, producing tests that either passed vacuously or errored on missing factories and routes. Projects should write tests tailored to their own domain.
