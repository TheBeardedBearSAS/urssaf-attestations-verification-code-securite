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

# The warnings are those of the generated dist/core.c, documented as harmless by instrument-hooks.
${CC:-cc} -O2 -g -shared -fPIC -fno-omit-frame-pointer \
    -Wno-maybe-uninitialized -Wno-unused-variable -Wno-unused-parameter -Wno-unused-but-set-variable -Wno-type-limits \
    -I "$src/includes" \
    "$src/dist/core.c" "$root/tools/codspeed/codspeed_php.c" \
    -o "$out/libcodspeed_php.so"

echo "Built $out/libcodspeed_php.so"
