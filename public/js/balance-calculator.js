/**
 * Common Balance Calculator — row checkboxes + sticky totals bar.
 * Expects window.__balanceCalc_<key> config per table with data-balance-calc="<key>".
 */
(function () {
  'use strict';

  function parseNumericFromCell(cell) {
    if (!cell) return 0;
    const input = cell.querySelector('input, select');
    if (input) {
      const v = input.value;
      if (v === '' || v === null || v === undefined) return 0;
      const n = parseFloat(String(v).replace(/,/g, ''));
      return Number.isFinite(n) ? n : 0;
    }
    const text = cell.textContent || '';
    const n = parseFloat(String(text).replace(/,/g, '').trim());
    return Number.isFinite(n) ? n : 0;
  }

  function BalanceCalculator(options) {
    this.table = options.table;
    if (!this.table) return;

    this.toggleBtn =
      typeof options.toggleButton === 'string'
        ? document.querySelector(options.toggleButton)
        : options.toggleButton;
    if (
      !Object.prototype.hasOwnProperty.call(options, 'skipRowSelector') ||
      options.skipRowSelector === undefined
    ) {
      this.skipRowSelector = '.salesman-row, .credit-header';
    } else {
      this.skipRowSelector = options.skipRowSelector;
    }
    this.columns = options.columns || [];
    this.stickyBar = document.getElementById('balanceCalcStickyBar');
    this.selectAllId = 'balanceCalcSelectAll_' + Math.random().toString(36).slice(2);

    this.active = false;
    this._boundRecalc = this.recalc.bind(this);

    if (this.toggleBtn) {
      this.toggleBtn.addEventListener('click', this.toggle.bind(this));
    }
  }

  BalanceCalculator.prototype.getDataRows = function () {
    const tbody = this.table.querySelector('tbody');
    if (!tbody) return [];
    return Array.from(tbody.querySelectorAll('tr')).filter((tr) => {
      if (
        this.skipRowSelector &&
        typeof tr.matches === 'function' &&
        tr.matches(this.skipRowSelector)
      )
        return false;
      if (tr.querySelector('td[colspan]')) return false;
      return tr.querySelectorAll('td').length > 0;
    });
  };

  BalanceCalculator.prototype.getNthChildIndex = function (originalNthChild1Based) {
    // When checkbox column is prepended, all data columns shift by +1
    return this.active ? originalNthChild1Based + 1 : originalNthChild1Based;
  };

  BalanceCalculator.prototype.getCellValue = function (row, col) {
    if (col.rowSelector) {
      const cell = row.querySelector(col.rowSelector);
      return parseNumericFromCell(cell);
    }
    if (typeof col.nthChild === 'number') {
      const idx = this.getNthChildIndex(col.nthChild);
      const cell = row.querySelector('td:nth-child(' + idx + ')');
      return parseNumericFromCell(cell);
    }
    return 0;
  };

  BalanceCalculator.prototype.insertCheckboxColumn = function () {
    const theadRow = this.table.querySelector('thead tr');
    if (!theadRow) return;

    this.table.classList.add('balance-calc-mode');

    const th = document.createElement('th');
    th.className = 'calc-checkbox-col hide-print';
    th.style.width = '42px';
    th.innerHTML =
      '<input type="checkbox" class="form-check-input" id="' +
      this.selectAllId +
      '" title="Select all">';
    theadRow.insertBefore(th, theadRow.firstChild);

    this.getDataRows().forEach((tr) => {
      const td = document.createElement('td');
      td.className = 'calc-checkbox-col hide-print text-center align-middle';
      td.innerHTML =
        '<input type="checkbox" class="form-check-input balance-calc-row-cb" aria-label="Select row">';
      tr.insertBefore(td, tr.firstChild);
    });

    this.table.querySelectorAll('tfoot tr').forEach(function (tr) {
      const firstCell = tr.cells[0];
      const tag = firstCell && firstCell.tagName === 'TH' ? 'th' : 'td';
      const cell = document.createElement(tag);
      cell.className = 'calc-checkbox-col hide-print';
      tr.insertBefore(cell, tr.firstChild);
    });

    const selectAll = document.getElementById(this.selectAllId);
    if (selectAll) {
      selectAll.addEventListener('change', () => {
        this.table.querySelectorAll('.balance-calc-row-cb').forEach((cb) => {
          cb.checked = selectAll.checked;
        });
        this.recalc();
      });
    }

    this.table.querySelectorAll('.balance-calc-row-cb').forEach((cb) => {
      cb.addEventListener('change', this._boundRecalc);
    });
  };

  BalanceCalculator.prototype.removeCheckboxColumn = function () {
    this.table.classList.remove('balance-calc-mode');
    this.table.querySelectorAll('.calc-checkbox-col').forEach((el) => el.remove());
    this.table.querySelectorAll('.balance-calc-row-cb').forEach((cb) => {
      cb.removeEventListener('change', this._boundRecalc);
    });
  };

  BalanceCalculator.prototype.recalc = function () {
    if (!this.stickyBar) return;

    const selected = this.table.querySelectorAll('.balance-calc-row-cb:checked');
    const count = selected.length;

    const totals = {};
    this.columns.forEach((c) => {
      totals[c.label] = 0;
    });

    selected.forEach((cb) => {
      const row = cb.closest('tr');
      if (!row) return;
      this.columns.forEach((col) => {
        totals[col.label] += this.getCellValue(row, col);
      });
    });

    const countEl = this.stickyBar.querySelector('[data-balance-calc-count]');
    if (countEl) countEl.textContent = String(count);

    this.columns.forEach((col) => {
      const el = this.stickyBar.querySelector('[data-balance-calc-total="' + col.key + '"]');
      if (el) el.textContent = formatMoney(totals[col.label]);
    });

    if (count > 0) {
      this.stickyBar.classList.remove('d-none');
      this.stickyBar.setAttribute('aria-hidden', 'false');
    } else {
      this.stickyBar.classList.add('d-none');
      this.stickyBar.setAttribute('aria-hidden', 'true');
    }
  };

  function formatMoney(n) {
    return Number(n).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  BalanceCalculator.prototype.toggle = function () {
    this.active = !this.active;
    if (this.active) {
      this.insertCheckboxColumn();
      if (this.toggleBtn) {
        this.toggleBtn.classList.add('active');
        this.toggleBtn.setAttribute('aria-pressed', 'true');
      }
      this.recalc();
    } else {
      this.removeCheckboxColumn();
      if (this.stickyBar) {
        this.stickyBar.classList.add('d-none');
        this.stickyBar.setAttribute('aria-hidden', 'true');
      }
      if (this.toggleBtn) {
        this.toggleBtn.classList.remove('active');
        this.toggleBtn.setAttribute('aria-pressed', 'false');
      }
    }
  };

  BalanceCalculator.prototype.closeBar = function () {
    if (this.stickyBar) {
      this.stickyBar.classList.add('d-none');
    }
  };

  function initFromTable(table) {
    const key = table.getAttribute('data-balance-calc');
    if (!key) return;

    const cfg = window['__balanceCalc_' + key];
    if (!cfg || !cfg.columns) return;

    const columns = cfg.columns.map(function (c, i) {
      return {
        key: c.key || 'col' + i,
        label: c.label,
        nthChild: c.nthChild,
        rowSelector: c.rowSelector,
      };
    });

    const instance = new BalanceCalculator({
      table: table,
      toggleButton: cfg.toggleButton,
      skipRowSelector: cfg.skipRowSelector,
      columns: columns,
    });

    const closeBtn = document.getElementById('balanceCalcStickyClose');
    if (closeBtn && !closeBtn.dataset.balanceCalcBound) {
      closeBtn.dataset.balanceCalcBound = '1';
      closeBtn.addEventListener('click', function () {
        document.querySelectorAll('[data-balance-calc-toggle].active').forEach(function (btn) {
          btn.click();
        });
      });
    }

    return instance;
  }

  function boot() {
    document.querySelectorAll('table[data-balance-calc]').forEach(initFromTable);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.BalanceCalculator = BalanceCalculator;
  window.initBalanceCalculatorTables = boot;
})();
