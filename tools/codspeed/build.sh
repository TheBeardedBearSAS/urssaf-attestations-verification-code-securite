#!/bin/sh
# This file is part of thebeardedbear/urssaf-attestations-verification-code-securite.
#
# Copyright (c) TheBeardedBear and the urssaf-attestations-verification-code-securite contributors
#
# SPDX-License-Identifier: MIT
#
# Builds var/codspeed/libcodspeed_php.so, the CodSpeed instrument-hooks library (pinned commit) loaded by
# bin/codspeed-bench through PHP FFI. Needs curl, tar and a C compiler.
# Usage: sh tools/codspeed/build.sh

set -eu

INSTRUMENT_HOOKS_COMMIT=8fd5b878499f819561b33219cdafaa6f8c28b4e4

root=$(cd "$(dirname "$0")/../.." && pwd)
out="$root/var/codspeed"
src="$out/instrument-hooks-$INSTRUMENT_HOOKS_COMMIT"

mkdir -p "$out"
if [ ! -d "$src" ]; then
    curl -fsSL "https://github.com/CodSpeedHQ/instrument-hooks/archive/$INSTRUMENT_HOOKS_COMMIT.tar.gz" | tar -xz -C "$out"
fi

# C11 as in the instrument-hooks example build (newer compilers default to C23, which dist/core.c does not build
# with); the warnings disabled are those of the generated dist/core.c.
${CC:-cc} -std=gnu11 -O2 -g -shared -fPIC \
    -Wno-maybe-uninitialized -Wno-unused-variable -Wno-unused-parameter -Wno-unused-but-set-variable -Wno-type-limits -Wno-format-security \
    -I "$src/includes" \
    "$src/dist/core.c" "$root/tools/codspeed/codspeed_php.c" \
    -o "$out/libcodspeed_php.so"

echo "Built $out/libcodspeed_php.so"
