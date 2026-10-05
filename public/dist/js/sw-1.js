document.addEventListener("DOMContentLoaded", () => {
    // --- CONFIG ---
    const ROOT_SELECTOR = "#root";
    const INDEX_PAGE = "index";

    // Auto-detect base path (fixes /0G20Project/public/ issues)
    const BASE_PATH = window.location.pathname
    .split("/")
    .slice(0, -1)
    .join("/") + "/";

    const ENTRY_URL = BASE_PATH;

    // --- STANDALONE PREVENTION ---
    (function preventStandalone() {
        const isHTMXRequest =
            document.head.querySelector('meta[name="htmx-request"]') !== null ||
            !!window.htmx?.config;

        const fromSameOrigin = document.referrer.startsWith(location.origin);

        if (!isHTMXRequest && !fromSameOrigin) {
            console.warn(" Direct access blocked.");

            if (
                window.location.pathname.endsWith("index.html") ||
                window.location.pathname === "/index"
            ) {
                window.location.replace(ENTRY_URL);
            }
        }
    })();

    // --- STATE ---
    let currentPage = null;
    let isLoading = false;

    function loadPage(pagePath) {
        if (!window.htmx) {
            console.error(" HTMX not loaded.");
            return;
        }

        if (isLoading) {
            console.warn(` Already loading: ${currentPage}`);
            return;
        }

        if (pagePath === currentPage) {
            console.info(` Already on page: ${pagePath}`);
            return;
        }

        const root = document.querySelector(ROOT_SELECTOR);
        if (!root) {
            console.error(` Target element "${ROOT_SELECTOR}" not found.`);
            return;
        }

        isLoading = true;

        htmx.ajax("GET", `${BASE_PATH}${pagePath}.html`, {
            target: ROOT_SELECTOR,
            swap: "innerHTML"
        })
        .catch(err => {
            console.error(` Failed to load ${pagePath}.html`, err);
        })
        .finally(() => {
            isLoading = false;
        });
    }

    function getHashPage() {
        let hash = window.location.hash.replace(/^#\/?/, "").trim();

        if (!hash) return INDEX_PAGE;

        hash = hash.replace(/\.html$/i, "");
        hash = hash.replace(/[^a-zA-Z0-9/_-]/g, "");

        if (hash === "index" || hash.startsWith("index/")) {
            return INDEX_PAGE;
        }

        return hash;
    }

    document.body.addEventListener("htmx:afterSwap", (event) => {
        try {
            const requestPath = event.detail?.xhr?.responseURL;
            if (!requestPath) return;

            const url = new URL(requestPath, window.location.origin);

            let path = url.pathname
                .replace(BASE_PATH, "")   // remove base path
                .replace(/^\//, "")
                .replace(/\.html$/, "");

            currentPage = path;
            window.location.hash = path;

        } catch (err) {
            console.error("❌ Error in afterSwap handler:", err);
        }
    });

    window.addEventListener("hashchange", () => {
        const page = getHashPage();
        loadPage(page);
    });

    // --- INIT ---
    const initialPage = getHashPage();
    loadPage(initialPage);
});