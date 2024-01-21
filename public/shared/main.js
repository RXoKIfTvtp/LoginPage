
function addEvent(element, eventName, func) {
	if (element.addEventListener) {
		element.addEventListener(eventName, func, false);
	} else if (element.attachEvent) {
		element.attachEvent('on' + eventName, func);
	}
}

function getUrlVal(v) {
	return new URL(window.location.href).searchParams.get(v);
}

function escapeHTML(unsafe) {
	return unsafe
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
 }

function load(url, callback) {
	var xobj = new XMLHttpRequest();
	xobj.overrideMimeType("text/plain");
	xobj.open("GET", url);
	xobj.onreadystatechange = function() {
		if (xobj.readyState == 4 && xobj.status == "200") {
			callback(xobj.responseText);
		} else if (xobj.readyState == 4 && xobj.status != "200") {
			callback(null);
		}
	};
	xobj.send(null);
}

function trySet(obj, nm, val) {
	if (typeof obj === "string" || obj instanceof String) {
		var handle = document.getElementById(obj);
	} else if (typeof obj === "object" || obj instanceof Object) {
		var handle = obj;
	} else {
		return false;
	}
	if (handle) {
		try {
			handle[nm] = val;
			return true;
		} catch (e) {
			// Swallow
		}
	}
	return false;
}

function apply(v, part = "innerHTML") {
	var x = getUrlVal(v);
	if (x != null) {
		if (trySet(v, part, x)) {
			var e = document.getElementById(v);
			if (e && e.style.display != "block") {
				e.style.display = "block";
			}
		}
	}
}

