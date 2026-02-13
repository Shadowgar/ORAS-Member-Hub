(function () {
	"use strict";

	if (!window.wp || !window.wp.apiFetch) {
		return;
	}

	var config = window.orasMemberHubMyTickets || {};
	var apiFetch = window.wp.apiFetch;
	var restBase = normalizeRestBase(config.restPathBase || "/oras-tickets/v1");
	var requestPath = restBase + "/me/tickets?scope=all&group_by=event&per_page=50";
	var printBase = normalizePrintBase(config.printBase || "/oras-ticket/print");
	var isLoggedIn = Boolean(config.isLoggedIn);
	var loginUrl = safeUrl(config.loginUrl || "");
	var containers = document.querySelectorAll(".oras-my-tickets[data-widget='oras-my-tickets']");

	if (!containers.length) {
		return;
	}

	if (config.nonce && typeof apiFetch.createNonceMiddleware === "function") {
		apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
	}

	containers.forEach(function (container) {
		loadTickets(container);
	});

	function loadTickets(container) {
		if (!isLoggedIn) {
			renderLoginRequired(container);
			return;
		}

		apiFetch({ path: requestPath, method: "GET" })
			.then(function (response) {
				renderTickets(container, response);
			})
			.catch(function (error) {
				renderError(container, error);
			});
	}

	function renderLoginRequired(container) {
		clearNode(container);
		var message = makeParagraph("Please log in to view tickets.");

		if (loginUrl) {
			var link = document.createElement("a");
			link.href = loginUrl;
			link.textContent = "Log in";
			message.appendChild(document.createTextNode(" "));
			message.appendChild(link);
		}

		container.appendChild(message);
	}

	function renderTickets(container, response) {
		var payload = getPayload(response);
		var upcoming = toArray(payload.upcoming);
		var past = toArray(payload.past);

		clearNode(container);

		if (!upcoming.length && !past.length) {
			container.appendChild(makeParagraph("You have no ticket purchases yet."));
			return;
		}

		if (upcoming.length) {
			container.appendChild(buildSection("Upcoming tickets", upcoming));
		}

		if (past.length) {
			container.appendChild(buildSection("Past purchases", past));
		}
	}

	function renderError(container, error) {
		clearNode(container);

		if (error && (error.status === 401 || error.status === 403)) {
			container.appendChild(makeParagraph("Please log in to view tickets."));
			return;
		}

		container.appendChild(makeParagraph("Unable to load tickets right now. Please try again."));
	}

	function buildSection(title, groups) {
		var section = document.createElement("section");
		section.className = "oras-my-tickets__section";

		var heading = document.createElement("h3");
		heading.className = "oras-my-tickets__heading";
		heading.textContent = title;
		section.appendChild(heading);

		var list = document.createElement("div");
		list.className = "oras-my-tickets__events";

		groups.forEach(function (group) {
			list.appendChild(buildEventGroup(group));
		});

		section.appendChild(list);

		return section;
	}

	function buildEventGroup(group) {
		var wrapper = document.createElement("article");
		wrapper.className = "oras-my-tickets__event";
		var event = group && typeof group === "object" ? group : {};
		var eventId = getEventId(event);

		var eventTitle = firstString([
			event.title,
			event.event_title,
			event.event && event.event.title
		], "Untitled event");
		var eventTitleText = decodeHtmlEntities(eventTitle);
		var eventUrl = safeUrl(firstString([
			event.event_url,
			event.url,
			event.event && event.event.event_url,
			event.event && event.event.url
		], ""));
		var eventStart = firstDefined([event.event_start], null);
		var orders = toArray(event.orders);

		var heading = document.createElement("h4");
		heading.className = "oras-my-tickets__event-title";

		if (eventUrl) {
			var eventLink = document.createElement("a");
			eventLink.href = eventUrl;
			eventLink.textContent = eventTitleText;
			eventLink.rel = "noopener noreferrer";
			heading.appendChild(eventLink);
		} else {
			heading.textContent = eventTitleText;
		}

		wrapper.appendChild(heading);

		var dateRow = makeParagraph(formatEventStartOrTbd(eventStart));
		dateRow.className = "oras-my-tickets__event-date";
		wrapper.appendChild(dateRow);

		var ticketCount = toNumber(firstDefined([event.total_qty, event.qty, event.quantity], 0), 0);
		var countRow = makeParagraph("You have " + String(ticketCount) + " tickets");
		countRow.className = "oras-my-tickets__event-count";
		wrapper.appendChild(countRow);

		var actions = document.createElement("p");
		actions.className = "oras-my-tickets__event-actions";

		if (eventUrl) {
			actions.appendChild(makeActionLink("View Event", eventUrl));
		}

		var printOrderList = appendPrintActions(actions, event, eventId, eventTitleText);

		if (actions.childNodes.length) {
			wrapper.appendChild(actions);
		}

		if (printOrderList) {
			wrapper.appendChild(printOrderList);
		}

		return wrapper;
	}

	function appendPrintActions(actionsNode, event, eventId, eventTitleText) {
		if (!actionsNode || !eventId) {
			if (!eventId) {
				console.warn("[ORAS Member Hub] Print unavailable: missing event_id for event group.", event);
			}
			actionsNode.appendChild(makeMutedText("Print unavailable"));
			return null;
		}

		var orderIds = getOrderIdsForEvent(event);
		var hasMultipleOrders = toArray(event.orders).length > 1 || orderIds.length > 1;

		if (!orderIds.length) {
			console.warn("[ORAS Member Hub] Print unavailable: missing order_id for event_id " + String(eventId) + ".", event);
			actionsNode.appendChild(makeMutedText("Print unavailable"));
			return null;
		}

		if (!hasMultipleOrders) {
			var singleOrderId = orderIds[0];
			var singlePrintUrl = buildPrintUrl(singleOrderId, eventId);
			var singleLink = makeActionLink("Print Tickets", singlePrintUrl);
			singleLink.setAttribute("aria-label", "Print tickets for " + eventTitleText + ", order " + String(singleOrderId));
			singleLink.dataset.example = "/oras-ticket/print?order_id=123&event_id=22";
			actionsNode.appendChild(singleLink);

			return null;
		}

		actionsNode.appendChild(makeMutedText("Print Tickets (choose order)"));

		var orderList = document.createElement("ul");
		orderList.className = "oras-my-tickets__orders oras-my-tickets__orders--print";

		orderIds.forEach(function (orderId, index) {
			var item = document.createElement("li");
			item.className = "oras-my-tickets__order oras-my-tickets__order--print";

			var printUrl = buildPrintUrl(orderId, eventId);
			var orderQty = getOrderQty(event, orderId);
			var qtyLabel = orderQty > 0 ? " (" + String(orderQty) + " tickets)" : "";
			var link = makeActionLink("Order #" + String(orderId) + " \u2014 Print" + qtyLabel, printUrl);
			link.setAttribute("aria-label", "Print tickets for " + eventTitleText + ", order " + String(orderId));

			if (index === 0) {
				link.dataset.example = "/oras-ticket/print?order_id=123&event_id=22";
			}

			item.appendChild(link);
			orderList.appendChild(item);
		});

		return orderList;
	}

	function getEventId(event) {
		var raw = firstDefined([
			event.event_id,
			event.id,
			event.event && event.event.id,
			event.event && event.event.event_id
		], null);

		if (raw === null || raw === undefined || raw === "") {
			return "";
		}

		return String(raw).trim();
	}

	function getOrderIdsForEvent(event) {
		var ids = [];
		var seen = {};
		var pushId = function (value) {
			if (value === null || value === undefined || value === "") {
				return;
			}

			var normalized = String(value).trim();
			if (!normalized || seen[normalized]) {
				return;
			}

			seen[normalized] = true;
			ids.push(normalized);
		};

		pushId(event.order_id);
		pushId(event.latest_order_id);

		toArray(event.order_ids).forEach(pushId);

		toArray(event.orders).forEach(function (order) {
			if (!order || typeof order !== "object") {
				return;
			}

			pushId(order.order_id);
			pushId(order.id);
		});

		return ids;
	}

	function getOrderQty(event, orderId) {
		var orders = toArray(event.orders);

		for (var i = 0; i < orders.length; i += 1) {
			var order = orders[i];
			if (!order || typeof order !== "object") {
				continue;
			}

			var candidate = firstDefined([order.order_id, order.id], "");
			if (String(candidate).trim() !== String(orderId)) {
				continue;
			}

			return toNumber(firstDefined([order.qty, order.quantity], 0), 0);
		}

		return 0;
	}

	function buildPrintUrl(orderId, eventId) {
		// Example: /oras-ticket/print?order_id=123&event_id=22
		var url = new URL(printBase, window.location.origin);
		url.searchParams.set("order_id", String(orderId));
		url.searchParams.set("event_id", String(eventId));

		return url.toString();
	}

	function makeActionLink(label, url) {
		var link = document.createElement("a");
		link.className = "oras-my-tickets__action";
		link.href = url;
		link.textContent = label;
		return link;
	}

	function makeMutedText(text) {
		var span = document.createElement("span");
		span.className = "oras-my-tickets__action is-disabled";
		span.textContent = text;
		return span;
	}

	function getPayload(response) {
		if (!response || typeof response !== "object") {
			return { upcoming: [], past: [] };
		}

		if (response.data && typeof response.data === "object") {
			response = response.data;
		}

		var upcoming = toArray(
			firstDefined([
				response.upcoming,
				response.upcoming_tickets,
				response.upcoming_events
			], [])
		);
		var past = toArray(
			firstDefined([
				response.past,
				response.past_purchases,
				response.past_tickets
			], [])
		);

		return {
			upcoming: upcoming,
			past: past
		};
	}

	function formatDateOrTbd(rawDate) {
		if (!rawDate) {
			return "Date TBD";
		}

		var date = new Date(rawDate);
		if (Number.isNaN(date.getTime())) {
			return "Date TBD";
		}

		return new Intl.DateTimeFormat(undefined, {
			year: "numeric",
			month: "short",
			day: "numeric"
		}).format(date);
	}

	function formatEventStartOrTbd(eventStart) {
		if (eventStart === null || eventStart === undefined || eventStart === "") {
			return "Date TBD";
		}

		var date = new Date(eventStart);
		if (Number.isNaN(date.getTime())) {
			return String(eventStart);
		}

		var datePart = new Intl.DateTimeFormat(undefined, {
			year: "numeric",
			month: "short",
			day: "numeric"
		}).format(date);
		var timePart = new Intl.DateTimeFormat(undefined, {
			hour: "numeric",
			minute: "2-digit"
		}).format(date);

		return datePart + " • " + timePart;
	}

	function normalizeRestBase(path) {
		var text = String(path || "/oras-tickets/v1");
		text = text.replace(/\/+$/, "");

		if (!text.startsWith("/")) {
			text = "/" + text;
		}

		return text;
	}

	function normalizePrintBase(path) {
		if (typeof path !== "string" || !path.trim()) {
			return window.location.origin + "/oras-ticket/print";
		}

		var text = path.trim();

		try {
			return new URL(text, window.location.origin).toString();
		} catch (error) {
			return window.location.origin + "/oras-ticket/print";
		}
	}

	function safeUrl(raw) {
		if (typeof raw !== "string" || !raw.trim()) {
			return "";
		}

		try {
			var url = new URL(raw, window.location.origin);
			var protocol = url.protocol.toLowerCase();
			return protocol === "http:" || protocol === "https:" ? url.toString() : "";
		} catch (error) {
			return "";
		}
	}

	function decodeHtmlEntities(value) {
		if (typeof value !== "string" || !value) {
			return "";
		}

		var parser = new DOMParser();
		var doc = parser.parseFromString(value, "text/html");
		return (doc.documentElement.textContent || "").trim();
	}

	function makeParagraph(text) {
		var p = document.createElement("p");
		p.textContent = text;
		return p;
	}

	function clearNode(node) {
		while (node.firstChild) {
			node.removeChild(node.firstChild);
		}
	}

	function toArray(value) {
		return Array.isArray(value) ? value : [];
	}

	function firstDefined(values, fallback) {
		for (var i = 0; i < values.length; i += 1) {
			if (values[i] !== undefined && values[i] !== null) {
				return values[i];
			}
		}

		return fallback;
	}

	function firstString(values, fallback) {
		var value = firstDefined(values, "");

		if (typeof value === "string" && value.trim()) {
			return value.trim();
		}

		return fallback || "";
	}

	function toNumber(value, fallback) {
		var parsed = Number(value);

		if (Number.isFinite(parsed)) {
			return parsed;
		}

		return fallback;
	}
})();
