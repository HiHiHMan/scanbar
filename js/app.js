(() => {
  const boxSearches = [0, 1, 2].map(index => {
    const number = index + 1;
    return { input: document.getElementById('search' + number), button: document.getElementById('searchButton' + number),
      clear: document.getElementById('clearSearch' + number), results: document.getElementById('searchResults' + number), request: 0, data: [] };
  });
  const printSelectedButtons = [1, 2, 3].map(number => document.getElementById('printSelected' + number));
  const selectedLabels = new Set();
  const connection = document.getElementById('connection');
  const count = document.getElementById('count');
  const updated = document.getElementById('updated');
  const boxes = [[], [], []];
  let activeBox = 0;
  const boxTabs = document.getElementById("boxTabs");

  const iconPaths = {
    search: '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>',
    x: '<path d="m18 6-12 12M6 6l12 12"></path>',
    printer: '<path d="M6 9V3h12v6"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v7H6z"></path>',
    trash: '<path d="M3 6h18M8 6V4h8v2m-11 0 1 15h10l1-15M10 10v7m4-7v7"></path>',
    plus: '<path d="M12 5v14M5 12h14"></path>',
    check: '<path d="m5 12 4 4L19 6"></path>',
    box: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="m3 8 9 5 9-5M3 8v9l9 5 9-5V8M12 13v9"></path>',
    layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="m3 12 9 5 9-5M3 16l9 5 9-5"></path>',
    barcode: '<path d="M4 5v14M7 5v14M10 5v14M14 5v14M16 5v14M20 5v14"></path>',
    package: '<path d="m12 3 9 5-9 5-9-5 9-5Z"></path><path d="M3 8v9l9 4 9-4V8M12 13v8"></path>'
  };

  function iconMarkup(name) {
    return '<svg class="ui-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + (iconPaths[name] || '') + '</svg>';
  }

  function setButtonIcon(button, name, label) {
    button.innerHTML = iconMarkup(name) + '<span>' + label + '</span>';
  }

  function decorateStaticButtons() {
    [1, 2, 3].forEach(number => setButtonIcon(document.getElementById('printSelected' + number), 'printer', 'พิมพ์ที่เลือก'));
    [1, 2, 3].forEach(number => {
      setButtonIcon(document.getElementById('printBox' + number), 'printer', 'พิมพ์ Box ' + number);
      setButtonIcon(document.getElementById('clearBox' + number), 'trash', 'ล้าง Box');
      setButtonIcon(document.getElementById('searchButton' + number), 'search', 'ค้นหา');
      setButtonIcon(document.getElementById('clearSearch' + number), 'x', 'ล้าง');
    });
  }


  function valueOf(row, aliases) {
    const keys = Object.keys(row);
    const normalized = new Map(keys.map(key => [key.toLowerCase().replace(/[^a-z0-9]/g, ''), key]));
    for (const alias of aliases) {
      const key = normalized.get(alias.toLowerCase().replace(/[^a-z0-9]/g, ''));
      if (key !== undefined && row[key] !== null && row[key] !== undefined && String(row[key]).trim() !== '') return String(row[key]).trim();
    }
    return '';
  }

  function field(row, aliases) { return valueOf(row, aliases) || '—'; }

  function labelData(row) {
    return {
      location: field(row, ['Location']),
      productID: field(row, ['ProductID']),
      size1: valueOf(row, ['PSize1']),
      size2: valueOf(row, ['PSize2']),
      size3: valueOf(row, ['PSize3']),
      description: field(row, ['Description']),
      barcode: valueOf(row, ['BarcodeNo'])
    };
  }

  function makeLine(className, label, text) {
    const line = document.createElement('div');
    line.className = 'label-line ' + className;
    const caption = document.createElement('span');
    caption.className = 'field-name';
    caption.textContent = label;
    line.append(caption, document.createTextNode(text));
    return line;
  }

  function makeLocationLine(location) {
    const line = document.createElement('div');
    line.className = 'label-line line-1';
    line.append(document.createTextNode('form '));
    const locationElement = document.createElement('strong');
    locationElement.textContent = location;
    line.appendChild(locationElement);
    return line;
  }

  function createLabelPreview(row) {
    const data = labelData(row);
    const preview = document.createElement('div');
    preview.className = 'label-preview';
    preview.appendChild(makeLocationLine(data.location));

    const productLine = document.createElement('div');
    productLine.className = 'label-line line-2';
    const productElement = document.createElement('span');
    productElement.className = 'productID';
    productElement.textContent = data.productID === '—' ? '-' : data.productID;
    const sizeElement = document.createElement('span');
    sizeElement.className = 'psize';
    sizeElement.textContent = [data.size1, data.size2, data.size3]
      .map(size => size && size !== '—' ? size : '9').join(' X ');
    productLine.append(productElement, sizeElement);
    preview.appendChild(productLine);
    preview.appendChild(makeLine('line-3', '', data.description));

    const barcodeLine = document.createElement('div');
    barcodeLine.className = 'label-line line-4';
    const barcodeBars = document.createElement('div');
    barcodeBars.className = 'barcode-bars';
    barcodeBars.textContent = data.barcode;
    barcodeLine.appendChild(barcodeBars);
    preview.appendChild(barcodeLine);
    return preview;
  }

  function addTableMessage(boxIndex, message, error = false) {
    const results = boxSearches[boxIndex].results;
    results.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 6;
    cell.className = 'table-message' + (error ? ' error' : '');
    cell.textContent = message;
    row.appendChild(cell);
    results.appendChild(row);
  }

  function itemKey(row) {
    const data = labelData(row);
    return [data.location, data.productID, data.barcode].join('|');
  }

  function addToBox(row, boxIndex) {
    if (boxes[boxIndex].some(item => itemKey(item) === itemKey(row))) return;
    boxes[boxIndex].push(row);
    renderBoxes();
    renderSearchResults(boxIndex);
  }

  function renderSearchResults(boxIndex) {
    const state = boxSearches[boxIndex];
    state.results.replaceChildren();
    if (!state.data.length) {
      addTableMessage(boxIndex, state.input.value.trim() ? 'ไม่พบ Product ID นี้' : 'กรอก Product ID เพื่อเริ่มค้นหา');
      return;
    }
    state.data.forEach(row => {
      const data = labelData(row);
      const tr = document.createElement('tr');
      [data.location, data.productID, [data.size1, data.size2, data.size3].filter(Boolean).join(' × ') || '—', data.description, data.barcode || '—'].forEach(value => {
        const td = document.createElement('td'); td.textContent = value; tr.appendChild(td);
      });
      const actionCell = document.createElement('td'); actionCell.className = 'add-actions';
      const exists = boxes[boxIndex].some(item => itemKey(item) === itemKey(row));
      const button = document.createElement('button'); button.type = 'button';
      button.className = exists ? 'secondary mini-button added-button' : 'mini-button add-button';
      setButtonIcon(button, exists ? 'check' : 'plus', exists ? 'เพิ่มแล้ว' : 'เพิ่มลง Box ' + (boxIndex + 1)); button.disabled = exists;
      button.addEventListener('click', () => addToBox(row, boxIndex));
      actionCell.appendChild(button); tr.appendChild(actionCell); state.results.appendChild(tr);
    });
  }

  function renderBoxes() {
    boxes.forEach((items, boxIndex) => {
      const number = boxIndex + 1;
      const container = document.getElementById('box' + number);
      const section = container.closest('.queue-section');
      section.classList.toggle('active-box', boxIndex === activeBox);
      section.setAttribute('role', 'tabpanel');
      section.setAttribute('aria-hidden', String(boxIndex !== activeBox));
      const boxCount = document.getElementById('box' + number + 'Count');
      const clearButton = document.getElementById('clearBox' + number);
      const printButton = document.getElementById('printBox' + number);
      boxCount.textContent = items.length.toLocaleString();
      clearButton.disabled = items.length === 0;
      printButton.disabled = items.length === 0;
      container.replaceChildren();

      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'empty';
        empty.textContent = 'ยังไม่มีรายการใน Box ' + number;
        container.appendChild(empty);
        return;
      }

      items.forEach((item, itemIndex) => {
        const card = document.createElement('article');
        card.className = 'item queue-item';
        card.dataset.box = String(boxIndex);
        card.dataset.itemIndex = String(itemIndex);
        const head = document.createElement('div');
        head.className = 'item-head';
        const label = document.createElement('span');
        label.className = 'item-index';
        label.textContent = 'ฉลาก ' + String(itemIndex + 1).padStart(2, '0');
        const selectionKey = boxIndex + '::' + itemKey(item);
        const selectWrap = document.createElement('label');
        selectWrap.className = 'label-select';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.checked = selectedLabels.has(selectionKey);
        checkbox.setAttribute('aria-label', 'เลือกฉลาก ' + (itemIndex + 1) + ' สำหรับพิมพ์');
        checkbox.addEventListener('change', () => {
          if (checkbox.checked) selectedLabels.add(selectionKey);
          else selectedLabels.delete(selectionKey);
          updatePrintSelectionUI();
        });
        selectWrap.append(checkbox, document.createTextNode('เลือกพิมพ์'));
        const printButton = document.createElement('button');
        printButton.type = 'button';
        printButton.className = 'label-action print-label-button';
        setButtonIcon(printButton, 'printer', 'พิมพ์');
        printButton.addEventListener('click', () => printSingleLabel(boxIndex, itemIndex));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove-button';
        setButtonIcon(remove, 'trash', 'ลบ');
        remove.addEventListener('click', () => {
          selectedLabels.delete(boxIndex + '::' + itemKey(item));
          boxes[boxIndex].splice(itemIndex, 1);
          renderBoxes();
          renderSearchResults(boxIndex);
        });
        head.append(label, selectWrap, printButton, remove);
        card.append(head, createLabelPreview(item));
        container.appendChild(card);
      });
    });
    updatePrintSelectionUI();
    boxTabs.replaceChildren();
    boxes.forEach((items, boxIndex) => {
      const tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'box-tab' + (boxIndex === activeBox ? ' active' : '');
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-selected', String(boxIndex === activeBox));
      tab.innerHTML = iconMarkup('box') + '<span>Box ' + (boxIndex + 1) + ' (' + items.length + ')</span>';
      tab.addEventListener('click', () => { activeBox = boxIndex; renderBoxes(); });
      boxTabs.appendChild(tab);
    });
  }

  function printSingleLabel(boxIndex, itemIndex) {
    // Do not open the print dialog unless this label still exists in the selected Box.
    if (!boxes[boxIndex] || !boxes[boxIndex][itemIndex]) return;

    let target = null;
    document.querySelectorAll('.queue-item').forEach(card => {
      card.classList.remove('print-target');
      const matches = Number(card.dataset.box) === boxIndex && Number(card.dataset.itemIndex) === itemIndex;
      if (matches) target = card;
    });
    if (!target) return;
    target.classList.add('print-target');
    window.print();
  }

  function updatePrintSelectionUI() {
    [0, 1, 2].forEach(boxIndex => {
      const number = boxIndex + 1;
      const count = boxes[boxIndex].filter(item => selectedLabels.has(boxIndex + '::' + itemKey(item))).length;
      document.getElementById('selectedCount' + number).textContent = count.toLocaleString();
      const button = printSelectedButtons[boxIndex];
      button.disabled = count === 0;
      setButtonIcon(button, 'printer', 'พิมพ์ที่เลือก');
    });
  }

  function printQueue(boxIndex = null, selectedOnly = false) {
    const cards = document.querySelectorAll('.queue-item');
    const printableCards = [];

    cards.forEach(card => {
      const cardBox = Number(card.dataset.box);
      const item = boxes[cardBox]?.[Number(card.dataset.itemIndex)];
      const key = item ? cardBox + '::' + itemKey(item) : '';
      const inRequestedBox = boxIndex === null || cardBox === boxIndex;
      if (item && inRequestedBox && (!selectedOnly || selectedLabels.has(key))) {
        printableCards.push(card);
      }
      card.classList.remove('print-target');
    });

    // Empty Box / no selected labels: do not invoke the browser print dialog.
    if (printableCards.length === 0) return;
    printableCards.forEach(card => card.classList.add('print-target'));
    window.print();
  }

  window.addEventListener('afterprint', () => {
    document.querySelectorAll('.queue-item').forEach(card => card.classList.remove('print-target'));
  });

  async function checkConnection() {
    try {
      const response = await fetch('/api/health');
      if (!response.ok) throw new Error('health check failed');
      connection.textContent = '● SQL Server เชื่อมต่อแล้ว';
      connection.style.background = '#e1f8eb';
      connection.style.color = '#17683a';
    } catch {
      connection.textContent = 'ไม่สามารถตรวจสอบ API ได้';
      connection.style.background = '#fee4e2';
      connection.style.color = '#b42318';
    }
  }

  async function searchByProductID(boxIndex) {
    const state = boxSearches[boxIndex], requestId = ++state.request, productID = state.input.value.trim();
    if (!productID) {
      state.data = []; renderSearchResults(boxIndex); count.textContent = 'พร้อมค้นหา'; updated.textContent = 'กรอก Product ID เพื่อค้นหา'; return;
    }
    addTableMessage(boxIndex, 'กำลังค้นหา Product ID…'); state.button.disabled = true;
    try {
      const response = await fetch('/api/labels/search?productID=' + encodeURIComponent(productID));
      const payload = await response.json();
      if (requestId !== state.request) return;
      if (response.status === 404) state.data = [];
      else if (!response.ok) throw new Error(payload.error || 'ค้นหาข้อมูลไม่สำเร็จ');
      else state.data = Array.isArray(payload.data) ? payload.data : [];
      renderSearchResults(boxIndex);
      count.textContent = 'Box ' + (boxIndex + 1) + ': ผลการค้นหา ' + state.data.length.toLocaleString() + ' รายการ';
      updated.textContent = 'ค้นหา Product ID: ' + productID + ' · ' + new Date().toLocaleString('th-TH');
    } catch (error) {
      if (requestId !== state.request) return;
      addTableMessage(boxIndex, error.message, true);
    } finally { if (requestId === state.request) state.button.disabled = false; }
  }

  boxSearches.forEach((state, boxIndex) => {
    state.input.addEventListener('keydown', event => {
      if (event.key !== 'Enter') return; event.preventDefault(); searchByProductID(boxIndex);
    });
    state.button.addEventListener('click', () => searchByProductID(boxIndex));
    state.clear.addEventListener('click', () => {
      state.input.value = ''; state.data = []; state.request++; state.button.disabled = false;
      renderSearchResults(boxIndex); count.textContent = 'พร้อมค้นหา';
      updated.textContent = 'ล้างคำค้นหา Box ' + (boxIndex + 1) + ' แล้ว'; state.input.focus();
    });
  });
  printSelectedButtons.forEach((button, boxIndex) => button.addEventListener('click', () => printQueue(boxIndex, true)));
  [0, 1, 2].forEach(index => {
    const number = index + 1;
    document.getElementById('clearBox' + number).addEventListener('click', () => {
      boxes[index].forEach(item => selectedLabels.delete(index + '::' + itemKey(item)));
      boxes[index] = [];
      renderBoxes();
      renderSearchResults(index);
    });
    document.getElementById('printBox' + number).addEventListener('click', () => printQueue(index));
  });

  decorateStaticButtons();
  renderBoxes();
  boxSearches.forEach((_, index) => renderSearchResults(index));
  checkConnection();
})();