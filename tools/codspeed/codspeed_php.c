/*
 * This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
 *
 * Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
 *
 * SPDX-License-Identifier: MIT
 */

/*
 * Exported versions of the header-only start/stop functions of the CodSpeed instrument-hooks library, which PHP FFI
 * cannot call directly: their callgrind client requests keep the measurement window tight around the subject.
 */

#include "core.h"

uint8_t codspeed_php_start_benchmark(InstrumentHooks *hooks) {
  return instrument_hooks_start_benchmark_inline(hooks);
}

uint8_t codspeed_php_stop_benchmark(InstrumentHooks *hooks) {
  return instrument_hooks_stop_benchmark_inline(hooks);
}
