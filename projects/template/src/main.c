////////////////////////////////////////
// { PROGRAM NAME } { VERSION }
// Author:
// License:
// Description:
////////////////////////////////////////

/*
* The comments in this file are here to guide you initially. Note that you shouldn't actually
* write comments that are pointless or obvious in your own code, write useful ones instead!
* See this for more details: https://ce-programming.github.io/toolchain/static/coding-guidelines.html
*
* Have fun!
*/

/* You probably want to keep these headers */
#include <stdbool.h>
#include <stddef.h>
#include <stdint.h>
#include <tice.h>

/* Here are some standard headers. Take a look at the toolchain for more. */
#include <math.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

/* Put function prototypes here or in a header (.h) file */

/* Note: uint8_t is an unsigned integer that can range from 0-255. */
/* It performs faster than just an int, so try to use it (or int8_t) when possible */
void printText(const char *text, uint8_t x, uint8_t y);

/* This is the entry point of your program. */
/* argc and argv can be there if you need to use arguments, see the toolchain example. */
int main(void)
{
    /* Initialize some strings */
    const char* HelloWorld = "Hello World!";
    const char* Welcome    = "Welcome to C!";

    /* Clear the homescreen */
    os_ClrHome();

    /* Print a few strings */
    printText(HelloWorld, 0, 0);
    printText(Welcome, 0, 1);

    /* Wait for a key press */
    while (!os_GetCSC());

    return 0;
}

/* Draw text on the homescreen at the given X/Y cursor location */
void printText(const char *text, uint8_t xpos, uint8_t ypos)
{
    os_SetCursorPos(ypos, xpos);
    os_PutStrFull(text);
}

