#include <unistd.h>

#include <cstdio>
#include <cstring>

#include <emscripten.h>
#include <glib.h>

#include <ticables.h>
#include <ticalcs.h>
#include <tifiles.h>

namespace {

constexpr int kCalcNotReady = 257;
constexpr int kCalcBusy = 269;
constexpr int kCableReadError = 3;
constexpr int kCableReadTimeout = 4;
constexpr int kCableWriteError = 5;
constexpr int kCableWriteTimeout = 6;

CableHandle* cable_handle = nullptr;
CalcHandle* calc_handle = nullptr;
CalcModel calc_model = CALC_NONE;
bool calc_attached = false;
bool calc_ready = false;
bool libraries_initialized = false;

void reset_connection()
{
    if (calc_handle) {
        if (calc_attached) {
            ticalcs_cable_detach(calc_handle);
        }
        ticalcs_handle_del(calc_handle);
    }
    calc_handle = nullptr;
    calc_attached = false;
    calc_ready = false;
    calc_model = CALC_NONE;

    if (cable_handle) {
        ticables_cable_close(cable_handle);
        ticables_handle_del(cable_handle);
    }
    cable_handle = nullptr;
}

int ensure_calc_attached(CableHandle* cable)
{
    if (!cable) {
        return -1;
    }

    if (calc_model == CALC_NONE) {
        const int probe_result = ticalcs_probe_usb_calc(cable, &calc_model);
        if (probe_result != 0 || calc_model == CALC_NONE) {
            return probe_result != 0 ? probe_result : -2;
        }
    }

    if (!calc_handle) {
        calc_handle = ticalcs_handle_new(calc_model);
        if (!calc_handle) {
            return -3;
        }
    }

    if (!calc_attached) {
        ticables_cable_close(cable);
        const int attach_result = ticalcs_cable_attach(calc_handle, cable);
        if (attach_result != 0) {
            return attach_result;
        }
        calc_attached = true;
        calc_ready = false;
    }

    return 0;
}

int ensure_calc_ready(CableHandle* cable)
{
    const int attach_result = ensure_calc_attached(cable);
    if (attach_result != 0) {
        return attach_result;
    }

    if (calc_ready) {
        return 0;
    }

    usleep(100000);
    int result = ticalcs_calc_isready(calc_handle);
    int retries = 0;
    while ((result == kCalcBusy
            || result == kCalcNotReady
            || result == kCableReadError
            || result == kCableReadTimeout
            || result == kCableWriteError
            || result == kCableWriteTimeout)
        && retries < 4) {
        usleep(100000);
        emscripten_sleep(100);
        result = ticalcs_calc_isready(calc_handle);
        ++retries;
    }

    calc_ready = result == 0;
    return result;
}

} // namespace

extern "C" {

EMSCRIPTEN_KEEPALIVE
int pb_transfer_init()
{
    if (libraries_initialized) {
        return 0;
    }

    // tilibs returns its positive instance count, not zero, from each init.
    ticables_library_init();
    tifiles_library_init();
    ticalcs_library_init();
    libraries_initialized = true;
    return 0;
}

EMSCRIPTEN_KEEPALIVE
CableHandle* pb_transfer_create_handle()
{
    if (cable_handle) {
        return cable_handle;
    }

    cable_handle = ticables_handle_new(CABLE_USB, PORT_1);
    if (cable_handle) {
        ticables_options_set_timeout(cable_handle, 50);
        ticables_options_set_delay(cable_handle, 10);
    }
    return cable_handle;
}

EMSCRIPTEN_KEEPALIVE
int pb_transfer_open_cable(CableHandle* cable)
{
    if (!cable) {
        return -1;
    }
    return ticables_cable_open(cable);
}

EMSCRIPTEN_KEEPALIVE
int pb_transfer_probe_model(CableHandle* cable)
{
    return ensure_calc_attached(cable);
}

EMSCRIPTEN_KEEPALIVE
int pb_transfer_get_calc_model()
{
    return static_cast<int>(calc_model);
}

EMSCRIPTEN_KEEPALIVE
const char* pb_transfer_get_calc_model_name()
{
    return ticalcs_model_to_string(calc_model);
}

EMSCRIPTEN_KEEPALIVE
int pb_transfer_send_file(CableHandle* cable, const char* filename)
{
    if (!cable || !filename || !*filename) {
        return -1;
    }
    if (!tifiles_file_is_ti(filename)) {
        return -2;
    }

    const int ready_result = ensure_calc_ready(cable);
    if (ready_result != 0) {
        return ready_result;
    }

    FileContent* content = tifiles_content_create_regular(calc_model);
    if (!content) {
        return -3;
    }

    int result = tifiles_file_read_regular(filename, content);
    if (result == 0) {
        result = ticalcs_calc_send_var(calc_handle, MODE_NORMAL, content);
    }
    tifiles_content_delete_regular(content);
    return result;
}

EMSCRIPTEN_KEEPALIVE
const char* pb_transfer_error_message(int code)
{
    static char buffer[256];
    char* message = nullptr;

    if (ticalcs_error_get(code, &message) == 0 && message) {
        std::snprintf(buffer, sizeof(buffer), "%s", message);
        ticalcs_error_free(message);
        return buffer;
    }
    message = nullptr;
    if (ticables_error_get(code, &message) == 0 && message) {
        std::snprintf(buffer, sizeof(buffer), "%s", message);
        ticables_error_free(message);
        return buffer;
    }
    message = nullptr;
    if (tifiles_error_get(code, &message) == 0 && message) {
        std::snprintf(buffer, sizeof(buffer), "%s", message);
        tifiles_error_free(message);
        return buffer;
    }

    std::snprintf(buffer, sizeof(buffer), "error %d", code);
    return buffer;
}

EMSCRIPTEN_KEEPALIVE
void pb_transfer_disconnect()
{
    reset_connection();
}

} // extern "C"

int main()
{
    return 0;
}
