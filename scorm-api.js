// SCORM 1.2 API wrapper — minimal, production-style SCO launcher
// Finds the LMS API in the opener/parent window chain, per SCORM spec.
var SCORM_API = null;

function findAPI(win) {
    var nFindAPITries = 0;
    while (win.API == null && win.parent != null && win.parent != win) {
        nFindAPITries++;
        if (nFindAPITries > 7) return null;
        win = win.parent;
    }
    return win.API;
}

function initSCORM() {
    var win = window;
    SCORM_API = findAPI(win);
    if (SCORM_API == null && win.opener != null && typeof win.opener != "undefined") {
        SCORM_API = findAPI(win.opener);
    }
    if (SCORM_API != null) {
        var result = SCORM_API.LMSInitialize("");
        if (result == "true") {
            SCORM_API.LMSSetValue("cmi.core.lesson_status", "incomplete");
            SCORM_API.LMSCommit("");
        }
    }
}

function completeModule(score) {
    if (SCORM_API != null) {
        SCORM_API.LMSSetValue("cmi.core.score.raw", String(score));
        SCORM_API.LMSSetValue("cmi.core.lesson_location", "module-end");
        SCORM_API.LMSSetValue("cmi.core.lesson_status", score >= 80 ? "passed" : "failed");
        SCORM_API.LMSCommit("");
        SCORM_API.LMSFinish("");
    }
}
