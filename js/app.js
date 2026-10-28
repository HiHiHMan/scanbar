(() => {
  const search = document.getElementById('search');
  const searchButton = document.getElementById('searchButton');
  const clearSearch = document.getElementById('clearSearch');
  const searchResults = document.getElementById('searchResults');
  const printSelected = document.getElementById('printSelected');
  const printAll = document.getElementById('printAll');
  const selectedLabels = new Set();
  const connection = document.getElementById('connection');
  const selectedCount = document.getElementById('selectedCount');
  const count = document.getElementById('count');
  const updated = document.getElementById('updated');

  let searchRequest = 0;
  let searchResultsData = [];
  const boxes = [[], [], []];
  let activeBox = 0;
  const boxTabs = document.getElementById("boxTabs");

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

  function addTableMessage(message, error = false) {
    searchResults.replaceChildren();
    const row = document.createElement('tr');
    const cell = document.createElement('td');
    cell.colSpan = 6;
    cell.className = 'table-message' + (error ? ' error' : '');
    cell.textContent = message;
    row.appendChild(cell);
    searchResults.appendChild(row);
  }

  function itemKey(row) {
    const data = labelData(row);
    return [data.location, data.productID, data.barcode].join('|');
  }

  function addToBox(row, boxIndex) {
    if (boxes[boxIndex].some(item => itemKey(item) === itemKey(row))) return;
    boxes[boxIndex].push(row);
    renderBoxes();
    renderSearchResults();
  }

  function renderSearchResults() {
    searchResults.replaceChildren();
    if (!searchResultsData.length) {
      addTableMessage(search.value.trim() ? 'ไม่พบ Product ID นี้' : 'กรอก Product ID เพื่อเริ่มค้นหา');
      return;
    }

    searchResultsData.forEach(row => {
      const data = labelData(row);
      const tr = document.createElement('tr');
      [data.location, data.productID, [data.size1, data.size2, data.size3].filter(Boolean).join(' × ') || '—', data.description, data.barcode || '—']
        .forEach(value => {
          const td = document.createElement('td');
          td.textContent = value;
          tr.appendChild(td);
        });

      const actionCell = document.createElement('td');
      actionCell.className = 'add-actions';
      boxes.forEach((box, boxIndex) => {
        const button = document.createElement('button');
        const exists = box.some(item => itemKey(item) === itemKey(row));
        button.type = 'button';
        button.className = exists ? 'secondary mini-button' : 'mini-button';
        button.textContent = exists ? 'เพิ่มแล้ว' : 'Box ' + (boxIndex + 1) + ' +';
        button.disabled = exists;
        button.addEventListener('click', () => addToBox(row, boxIndex));
        actionCell.appendChild(button);
      });
      tr.appendChild(actionCell);
      searchResults.appendChild(tr);
    });
  }

  function renderBoxes() {
    let total = 0;
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
      total += items.length;
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
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove-button';
        remove.textContent = 'ลบ';
        remove.addEventListener('click', () => {
          selectedLabels.delete(boxIndex + '::' + itemKey(item));
          boxes[boxIndex].splice(itemIndex, 1);
          renderBoxes();
          renderSearchResults();
        });
        head.append(label, selectWrap, remove);
        card.append(head, createLabelPreview(item));
        container.appendChild(card);
      });
    });
    selectedCount.textContent = total.toLocaleString();
    printAll.disabled = total === 0;
    updatePrintSelectionUI();
    boxTabs.replaceChildren();
    boxes.forEach((items, boxIndex) => {
      const tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'box-tab' + (boxIndex === activeBox ? ' active' : '');
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-selected', String(boxIndex === activeBox));
      tab.textContent = 'Box ' + (boxIndex + 1) + ' (' + items.length + ')';
      tab.addEventListener('click', () => { activeBox = boxIndex; renderBoxes(); });
      boxTabs.appendChild(tab);
    });
  }

  function updatePrintSelectionUI() {
    const selectedCount = selectedLabels.size;
    printSelected.disabled = selectedCount === 0;
    printSelected.textContent = 'พิมพ์ฉลากที่เลือก (' + selectedCount + ')';
  }

  function printQueue(boxIndex = null, selectedOnly = false) {
    const cards = document.querySelectorAll('.queue-item');
    let hasItems = false;
    cards.forEach(card => {
      const item = boxes[Number(card.dataset.box)]?.[Number(card.dataset.itemIndex)];
      const key = item ? Number(card.dataset.box) + '::' + itemKey(item) : '';
      const inRequestedBox = boxIndex === null || Number(card.dataset.box) === boxIndex;
      const shouldPrint = inRequestedBox && (!selectedOnly || selectedLabels.has(key));
      card.classList.toggle('print-target', shouldPrint);
      if (shouldPrint) hasItems = true;
    });
    if (hasItems) window.print();
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

  async function searchByProductID() {
    const requestId = ++searchRequest;
    const productID = search.value.trim();
    if (!productID) {
      searchResultsData = [];
      renderSearchResults();
      count.textContent = 'พร้อมค้นหา';
      updated.textContent = 'กรอก Product ID เพื่อค้นหา';
      return;
    }

    addTableMessage('กำลังค้นหา Product ID…');
    searchButton.disabled = true;
    try {
      const response = await fetch('/api/labels/search?productID=' + encodeURIComponent(productID));
      const payload = await response.json();
      if (requestId !== searchRequest) return;
      if (response.status === 404) {
        searchResultsData = [];
      } else if (!response.ok) {
        throw new Error(payload.error || 'ค้นหาข้อมูลไม่สำเร็จ');
      } else {
        searchResultsData = Array.isArray(payload.data) ? payload.data : [];
      }
      renderSearchResults();
      count.textContent = 'ผลการค้นหา ' + searchResultsData.length.toLocaleString() + ' รายการ';
      updated.textContent = 'ค้นหา Product ID: ' + productID + ' · ' + new Date().toLocaleString('th-TH');
    } catch (error) {
      if (requestId !== searchRequest) return;
      addTableMessage(error.message, true);
    } finally {
      if (requestId === searchRequest) searchButton.disabled = false;
    }
  }

  search.addEventListener('keydown', event => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    searchByProductID();
  });
  searchButton.addEventListener('click', searchByProductID);
  clearSearch.addEventListener('click', () => {
    search.value = '';
    searchResultsData = [];
    searchRequest++;
    searchButton.disabled = false;
    renderSearchResults();
    count.textContent = 'พร้อมค้นหา';
    updated.textContent = 'ล้างคำค้นหาแล้ว';
    search.focus();
  });
  printSelected.addEventListener('click', () => printQueue(null, true));
  printAll.addEventListener('click', () => printQueue());
  [0, 1, 2].forEach(index => {
    const number = index + 1;
    document.getElementById('clearBox' + number).addEventListener('click', () => {
      boxes[index].forEach(item => selectedLabels.delete(index + '::' + itemKey(item)));
      boxes[index] = [];
      renderBoxes();
      renderSearchResults();
    });
    document.getElementById('printBox' + number).addEventListener('click', () => printQueue(index));
  });

  renderBoxes();
  renderSearchResults();
  checkConnection();
})();