(function () {
	"use strict";

	if (!window.wp || !window.wp.apiFetch) {
		return;
	}

	var config = window.orasMemberHubMyTickets || {};
	var apiFetch = window.wp.apiFetch;
	var restBase = normalizeRestBase(config.restPathBase || "/oras-tickets/v1");
	var requestPath = restBase + "/me/tickets?scope=all&group_by=event&per_page=50";
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
		apiFetch({ path: requestPath, method: "GET" })
			.then(function (response) {
				renderTickets(container, response);
			})
			.catch(function () {
				renderError(container);
			});
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

	function renderError(container) {
		clearNode(container);
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

		var ticketCount = toNumber(event.total_qty, 0);
		var countRow = makeParagraph("Tickets: " + String(ticketCount));
		countRow.className = "oras-my-tickets__event-count";
		wrapper.appendChild(countRow);

		if (orders.length) {
			var orderList = document.createElement("ul");
			orderList.className = "oras-my-tickets__orders";

			orders.forEach(function (order) {
				orderList.appendChild(buildOrderRow(order));
			});

			wrapper.appendChild(orderList);
		}

		return wrapper;
	}

	function buildOrderRow(order) {
		var item = document.createElement("li");
		item.className = "oras-my-tickets__order";

		var orderUrl = safeUrl(firstString([order.order_view_url, order.view_url, order.url], ""));
		var orderDate = firstString([order.order_date, order.date, order.created_at], "");
		var qty = toNumber(order.qty, toNumber(order.quantity, 0));

		if (orderUrl) {
			var link = document.createElement("a");
			link.href = orderUrl;
			link.textContent = "View / print order";
			link.rel = "noopener noreferrer";
			item.appendChild(link);
		} else {
			var fallbackText = document.createElement("span");
			fallbackText.textContent = "View / print order";
			item.appendChild(fallbackText);
		}

		var meta = document.createElement("span");
		meta.className = "oras-my-tickets__order-meta";
		meta.textContent = " - " + formatDateOrTbd(orderDate) + " - Qty: " + String(qty);
		item.appendChild(meta);

		return item;
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
