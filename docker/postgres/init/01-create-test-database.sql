-- Runs once, when the container's data volume is first created.
-- A separate database for tests, so running the suite never touches dev data.
CREATE DATABASE bathys_test OWNER bathys;
