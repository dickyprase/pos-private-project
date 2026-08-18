# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {SANCTUM_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Ambil token melalui <code>POST /api/auth/token</code>, lalu gunakan sebagai Bearer token.
