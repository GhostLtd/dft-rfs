// ZAP standalone script for bin/dast's DAST_RORO_REAUTH test hook (docker/dast/plans/roro.template.yaml): the RoRo
// section's ZAP user forgets its login, by clearing its cookie jar (the session and the remember-me cookie). The app
// then sees it as logged out, so ZAP's next poll must find that and log in again with a new link. See docs/Dast.md.

var Control = Java.type("org.parosproxy.paros.control.Control");
var Model = Java.type("org.parosproxy.paros.model.Model");
var ExtensionUserManagement = Java.type("org.zaproxy.zap.extension.users.ExtensionUserManagement");

var extension = Control.getSingleton().getExtensionLoader().getExtension(ExtensionUserManagement.class);
var context = Model.getSingleton().getSession().getContext("roro");
var users = extension.getContextUserAuthManager(context.getId()).getUsers();

var cleared = false;
for (var i = 0; i < users.size(); i++) {
	var user = users.get(i);
	if (user.getName() === "roro" && user.getAuthenticatedSession() !== null) {
		var cookies = user.getAuthenticatedSession().getHttpState().getCookies().length;
		user.getAuthenticatedSession().getHttpState().clearCookies();
		print("DAST-RORO: the roro user forgot its login (" + cookies + " cookies cleared)");
		cleared = true;
	}
}
if (!cleared) {
	print("DAST-RORO-FATAL: the roro user had no login to forget");
}
