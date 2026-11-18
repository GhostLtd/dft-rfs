// ZAP authentication script for bin/dast's RoRo section (docker/dast/plans/roro.template.yaml, smoke.yaml). See
// docs/Dast.md.
//
// RoRo logs in with single-use links (POST expires, user and hash to /roro/authenticate). The DAST seed command
// issues a pool of them, which bin/dast hands to ZAP as DAST_RORO_LOGIN_LINKS: space-separated POST bodies. Each call
// spends the next one. The optional "Links variable" parameter names another pool (the roro-forms section's user has
// its own, DAST_RORO_FORMS_LOGIN_LINKS). Out of links, it prints DAST-RORO-FATAL (bin/dast fails the section on that line: ZAP itself
// only logs a failed login) and returns no message.

var HttpRequestHeader = Java.type("org.parosproxy.paros.network.HttpRequestHeader");
var HttpHeader = Java.type("org.parosproxy.paros.network.HttpHeader");
var URI = Java.type("org.apache.commons.httpclient.URI");
var ScriptVars = Java.type("org.zaproxy.zap.extension.script.ScriptVars");
var JavaSystem = Java.type("java.lang.System");

// One counter per ZAP process, which bin/dast runs per section, so per pool. (ZAP's script variable keys must be
// under 30 characters.)
var USED = "dast.roro.links.used";

function authenticate(helper, paramsValues, _credentials) {
	var variable = String(paramsValues.get("Links variable") || "").trim() || "DAST_RORO_LOGIN_LINKS";
	var pool = String(JavaSystem.getenv(variable) || "").trim();
	var links = pool === "" ? [] : pool.split(/\s+/);
	var used = parseInt(ScriptVars.getGlobalVar(USED) || "0", 10);

	if (used >= links.length) {
		print("DAST-RORO-FATAL: login links exhausted (" + links.length + " in the pool)");
		return null;
	}

	ScriptVars.setGlobalVar(USED, String(used + 1));
	print("DAST-RORO: login link " + (used + 1) + " of " + links.length + " used");

	var msg = helper.prepareMessage();
	msg.setRequestHeader(new HttpRequestHeader(HttpRequestHeader.POST, new URI(paramsValues.get("Login URL"), false), HttpHeader.HTTP11));
	msg.getRequestHeader().setHeader(HttpHeader.CONTENT_TYPE, "application/x-www-form-urlencoded");
	msg.setRequestBody(links[used]);
	msg.getRequestHeader().setContentLength(msg.getRequestBody().length());
	helper.sendAndReceive(msg);

	return msg;
}

function getRequiredParamsNames() {
	return ["Login URL"];
}

function getOptionalParamsNames() {
	return ["Links variable"];
}

function getCredentialsParamsNames() {
	return [];
}
