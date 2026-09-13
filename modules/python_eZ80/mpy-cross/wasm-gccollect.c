/* SPDX-License-Identifier: MIT
 * The one-shot browser compiler disables automatic garbage collection.
 * WebAssembly locals are invisible to the old setjmp-based root scanner.
 * Abort on any unexpected collection attempt instead of freeing live objects.
 */
#include <stdio.h>
#include <stdlib.h>
#include "py/gc.h"

void gc_collect(void) {
    fputs("Unexpected garbage collection in the browser compiler\n", stderr);
    abort();
}
