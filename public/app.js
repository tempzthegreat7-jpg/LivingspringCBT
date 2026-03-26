window.addEventListener("load", () => {
  const csrfMeta = document.querySelector("meta[name='csrf-token']");
  const csrfToken = csrfMeta ? String(csrfMeta.getAttribute("content") || "") : "";

  if (csrfToken) {
    const nativeFetch = window.fetch ? window.fetch.bind(window) : null;
    if (nativeFetch) {
      window.fetch = (input, init = {}) => {
        const nextInit = { ...init };
        const nextHeaders = new Headers(init.headers || {});
        if (!nextHeaders.has("X-CSRF-Token")) {
          nextHeaders.set("X-CSRF-Token", csrfToken);
        }
        nextInit.headers = nextHeaders;
        return nativeFetch(input, nextInit);
      };
    }

    const postForms = Array.from(document.querySelectorAll("form[method='POST']"));
    postForms.forEach((form) => {
      if (form.querySelector("input[name='_token']")) return;
      const tokenInput = document.createElement("input");
      tokenInput.type = "hidden";
      tokenInput.name = "_token";
      tokenInput.value = csrfToken;
      form.appendChild(tokenInput);
    });
  }

  const initWarningUi = () => {
    const styleId = "app-warning-style";
    if (!document.getElementById(styleId)) {
      const style = document.createElement("style");
      style.id = styleId;
      style.textContent = `
        .app-warning-overlay{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.55);padding:16px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .24s ease,visibility .24s ease}
        .app-warning-overlay.active{opacity:1;visibility:visible;pointer-events:auto}
        .app-warning-card{width:min(420px,100%);background:#fff;border-radius:16px;padding:22px 20px;box-shadow:0 20px 40px rgba(15,23,42,.26);border:1px solid #e2e8f0;opacity:0;transform:translateY(14px) scale(.98);transition:opacity .24s ease,transform .24s ease}
        .app-warning-overlay.active .app-warning-card{opacity:1;transform:translateY(0) scale(1)}
        .app-warning-head{display:flex;align-items:center;gap:12px;margin-bottom:12px}
        .app-warning-sign{width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;background:#fee2e2;color:#991b1b;font-weight:800;font-size:15px}
        .app-warning-title{font-size:1.05rem;font-weight:800;color:#172033;margin:0}
        .app-warning-message{margin:0 0 16px;color:#334155;line-height:1.5}
        .app-warning-actions{display:flex;justify-content:flex-end;gap:10px}
        .app-warning-btn{border:0;border-radius:10px;padding:9px 14px;font-weight:700;cursor:pointer}
        .app-warning-btn.cancel{background:#e2e8f0;color:#1f2937}
        .app-warning-btn.ok{background:#b91c1c;color:#fff}
        .app-warning-overlay.is-success .app-warning-sign{background:#dcfce7;color:#166534}
        .app-warning-overlay.is-success .app-warning-btn.ok{background:#15803d;color:#fff}
      `;
      document.head.appendChild(style);
    }

    let overlay = document.getElementById("appWarningOverlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.id = "appWarningOverlay";
      overlay.className = "app-warning-overlay";
      overlay.setAttribute("aria-hidden", "true");
      overlay.innerHTML = `
        <div class="app-warning-card" role="dialog" aria-modal="true" aria-labelledby="appWarningTitle">
          <div class="app-warning-head">
            <span class="app-warning-sign" id="appWarningSign" aria-hidden="true">!</span>
            <h2 class="app-warning-title" id="appWarningTitle">Warning</h2>
          </div>
          <p class="app-warning-message" id="appWarningMessage"></p>
          <div class="app-warning-actions">
            <button type="button" class="app-warning-btn cancel" id="appWarningCancel">Cancel</button>
            <button type="button" class="app-warning-btn ok" id="appWarningOk">Continue</button>
          </div>
        </div>
      `;
      document.body.appendChild(overlay);
    }

    const titleEl = document.getElementById("appWarningTitle");
    const signEl = document.getElementById("appWarningSign");
    const messageEl = document.getElementById("appWarningMessage");
    const cancelBtn = document.getElementById("appWarningCancel");
    const okBtn = document.getElementById("appWarningOk");
    if (!titleEl || !signEl || !messageEl || !cancelBtn || !okBtn) {
      return;
    }

    let resolver = null;
    const close = (result) => {
      overlay.classList.remove("active");
      overlay.setAttribute("aria-hidden", "true");
      if (resolver) {
        const next = resolver;
        resolver = null;
        next(result);
      }
    };

    const open = (message, options = {}) =>
      new Promise((resolve) => {
        resolver = resolve;
        const mode = String(options.mode || "confirm");
        const variant = String(options.variant || "warning");
        const isSuccess = variant === "success";
        const defaultTitle = isSuccess ? "Success" : "Warning";
        titleEl.textContent = String(options.title || defaultTitle);
        signEl.textContent = isSuccess ? "✓" : "!";
        overlay.classList.toggle("is-success", isSuccess);
        messageEl.textContent = String(message || "");
        cancelBtn.style.display = mode === "alert" ? "none" : "";
        okBtn.textContent = mode === "alert" ? "OK" : "Continue";
        overlay.classList.add("active");
        overlay.setAttribute("aria-hidden", "false");
        okBtn.focus();
      });

    cancelBtn.addEventListener("click", () => close(false));
    okBtn.addEventListener("click", () => close(true));
    overlay.addEventListener("click", (event) => {
      if (event.target === overlay) {
        close(false);
      }
    });
    document.addEventListener("keydown", (event) => {
      if (!overlay.classList.contains("active")) return;
      if (event.key === "Escape") {
        close(false);
      }
    });

    window.AppWarning = {
      confirm: (message, options = {}) => open(message, { ...options, mode: "confirm" }),
      alert: (message, options = {}) => open(message, { ...options, mode: "alert" }).then(() => {}),
    };

    document.addEventListener(
      "submit",
      (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const warningMessage = form.getAttribute("data-warning-confirm");
        if (!warningMessage) return;
        if (form.dataset.warningApproved === "1") {
          form.dataset.warningApproved = "0";
          return;
        }

        event.preventDefault();
        window.AppWarning
          .confirm(warningMessage, { title: form.getAttribute("data-warning-title") || "Warning" })
          .then((approved) => {
            if (!approved) return;
            form.dataset.warningApproved = "1";
            form.submit();
          });
      },
      true
    );
  };

  initWarningUi();

  const transitionEl = document.querySelector(".transition");

  if (transitionEl) {
    setTimeout(() => {
      transitionEl.classList.remove("is-active");
    }, 150);
  }

  const anchors = document.querySelectorAll("a[href]");

  anchors.forEach((anchor) => {
    anchor.addEventListener("click", (event) => {
      const href = anchor.getAttribute("href");

      if (!transitionEl || !href) return;
      if (href.startsWith("#")) return;
      if (anchor.target === "_blank") return;
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

      event.preventDefault();
      transitionEl.classList.add("is-active");

      setTimeout(() => {
        window.location.href = href;
      }, 350);
    });
  });

  const initDataTables = () => {
    const tables = Array.from(document.querySelectorAll("table[data-enhance-table='1']"));

    tables.forEach((table, tableIndex) => {
      const tbody = table.tBodies && table.tBodies[0] ? table.tBodies[0] : null;
      if (!tbody) return;

      const rows = Array.from(tbody.rows);
      if (rows.length === 0) return;

      const controls = document.querySelector(`[data-table-controls='${table.id}']`);
      if (!controls) return;

      const searchInput = controls.querySelector("[data-table-search]");
      const pageSizeSelect = controls.querySelector("[data-table-size]");
      const filterInputs = Array.from(controls.querySelectorAll("[data-table-filter]"));
      const pagination = controls.querySelector("[data-table-pagination]");
      const status = controls.querySelector("[data-table-status]");

      const state = {
        query: "",
        filters: {},
        page: 1,
        pageSize: Number(pageSizeSelect ? pageSizeSelect.value : 10) || 10,
        sortIndex: -1,
        sortDirection: "asc",
        filteredRows: rows.slice(),
      };

      const updateSortHeaderState = () => {
        const headers = Array.from(table.querySelectorAll("thead th[data-sortable='1']"));
        headers.forEach((th) => {
          const col = Number(th.dataset.sortCol || -1);
          th.removeAttribute("aria-sort");
          if (col === state.sortIndex) {
            th.setAttribute("aria-sort", state.sortDirection === "asc" ? "ascending" : "descending");
          }
        });
      };

      const sortRows = () => {
        if (state.sortIndex < 0) return;

        const dir = state.sortDirection === "asc" ? 1 : -1;
        state.filteredRows.sort((rowA, rowB) => {
          const aText = (rowA.cells[state.sortIndex] ? rowA.cells[state.sortIndex].innerText : "").trim();
          const bText = (rowB.cells[state.sortIndex] ? rowB.cells[state.sortIndex].innerText : "").trim();
          const aNum = Number(aText.replace(/[^\d.-]/g, ""));
          const bNum = Number(bText.replace(/[^\d.-]/g, ""));

          if (!Number.isNaN(aNum) && !Number.isNaN(bNum) && aText !== "" && bText !== "") {
            return (aNum - bNum) * dir;
          }

          return aText.localeCompare(bText, undefined, { sensitivity: "base" }) * dir;
        });
      };

      const filterRows = () => {
        const query = state.query.trim().toLowerCase();
        state.filteredRows = rows.filter((row) => {
          const matchesQuery = !query || row.innerText.toLowerCase().includes(query);
          if (!matchesQuery) {
            return false;
          }

          return Object.entries(state.filters).every(([filterKey, filterValue]) => {
            const normalizedValue = String(filterValue || "").trim().toLowerCase();
            if (!normalizedValue) {
              return true;
            }

            const rowValue = String((row.dataset && row.dataset[filterKey]) || "").trim().toLowerCase();
            return rowValue === normalizedValue;
          });
        });
      };

      const renderPagination = (totalPages) => {
        if (!pagination) return;
        pagination.innerHTML = "";

        const createBtn = (label, targetPage, disabled, ariaLabel) => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "table-page-btn";
          btn.textContent = label;
          btn.disabled = disabled;
          btn.setAttribute("aria-label", ariaLabel);
          btn.addEventListener("click", () => {
            state.page = targetPage;
            render();
          });
          return btn;
        };

        pagination.appendChild(
          createBtn("Prev", Math.max(1, state.page - 1), state.page <= 1, "Previous page")
        );

        const maxButtons = 5;
        const startPage = Math.max(1, state.page - 2);
        const endPage = Math.min(totalPages, startPage + maxButtons - 1);

        for (let p = startPage; p <= endPage; p += 1) {
          const btn = createBtn(String(p), p, false, `Page ${p}`);
          if (p === state.page) {
            btn.classList.add("active");
            btn.setAttribute("aria-current", "page");
          }
          pagination.appendChild(btn);
        }

        pagination.appendChild(
          createBtn("Next", Math.min(totalPages, state.page + 1), state.page >= totalPages, "Next page")
        );
      };

      const render = () => {
        filterRows();
        sortRows();

        const total = state.filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page = Math.min(Math.max(1, state.page), totalPages);

        const start = (state.page - 1) * state.pageSize;
        const end = start + state.pageSize;
        const visibleSet = new Set(state.filteredRows.slice(start, end));

        rows.forEach((row) => {
          row.hidden = !visibleSet.has(row);
        });

        if (status) {
          const from = total === 0 ? 0 : start + 1;
          const to = Math.min(total, end);
          status.textContent = `Showing ${from}-${to} of ${total}`;
        }

        renderPagination(totalPages);
        updateSortHeaderState();
      };

      if (searchInput) {
        searchInput.addEventListener("input", () => {
          state.query = searchInput.value || "";
          state.page = 1;
          render();
        });
      }

      if (pageSizeSelect) {
        pageSizeSelect.addEventListener("change", () => {
          state.pageSize = Number(pageSizeSelect.value) || 10;
          state.page = 1;
          render();
        });
      }

      filterInputs.forEach((input) => {
        const filterKey = String(input.getAttribute("data-table-filter") || "").trim();
        if (!filterKey) return;

        state.filters[filterKey] = input.value || "";
        input.addEventListener("input", () => {
          state.filters[filterKey] = input.value || "";
          state.page = 1;
          render();
        });
        input.addEventListener("change", () => {
          state.filters[filterKey] = input.value || "";
          state.page = 1;
          render();
        });
      });

      const sortableHeaders = Array.from(table.querySelectorAll("thead th[data-sortable='1']"));
      sortableHeaders.forEach((th) => {
        const colIndex = Number(th.dataset.sortCol || -1);
        if (colIndex < 0) return;

        th.tabIndex = 0;
        th.role = "button";
        th.setAttribute("aria-label", `${th.innerText.trim()} sortable column`);

        const activateSort = () => {
          if (state.sortIndex === colIndex) {
            state.sortDirection = state.sortDirection === "asc" ? "desc" : "asc";
          } else {
            state.sortIndex = colIndex;
            state.sortDirection = "asc";
          }
          state.page = 1;
          render();
        };

        th.addEventListener("click", activateSort);
        th.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            activateSort();
          }
        });
      });

      table.dataset.enhancedInstance = String(tableIndex);
      render();
    });
  };

  initDataTables();
});
