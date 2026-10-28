(() => {
  const search = document.getElementById('search');
  const searchButton = document.getElementById('searchButton');
  const clearSearch = document.getElementById('clearSearch');
  const searchResults = document.getElementById('searchResults');
  const selectedLabels = document.getElementById('selectedLabels');
  const printSelected = document.getElementById('printSelected');
  const clearQueue = document.getElementById('clearQueue');
  const connection = document.getElementById('connection');
  const selectedCount = document.getElementById('selectedCount');
  const count = document.getElementById('count');
  const updated = document.getElementById('updated');

  let searchRequest = 0;
  let searchResultsData = [];
  let selectedItems = [];

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

  function addMessage(container, message, error = false) {
    container.replaceChildren();
    const element = document.createElement('div');
    element.className = 'empty' + (error ? ' error' : '');
    element.textContent = message;
    container.appendChild(element);
  }

  function itemKey(row) {
    return JSON.stringify(row);
  }

  function createResultCard(row, index) {
    const card = document.createElement('article');
    card.className = 'item';
    card.appendChild(createLabelPreview(row));

    const actions = document.createElement('div');
    actions.className = 'item-actions';
    const addButton = document.createElement('button');
    const alreadyAdded = selectedItems.some(item => itemKey(item) === itemKey(row));
    addButton.type = 'button';
    addButton.className = alreadyAdded ? 'secondary' : '';
    addButton.textContent = alreadyAdded ? 'เพิ่มแล้ว' : '+ เพิ่มรายการ';
    addButton.disabled = alreadyAdded;
    addButton.addEventListener('click', () => {
      if (selectedItems.some(item => itemKey(item) === itemKey(row))) return;
      selectedItems.push(row);
      renderQueue();
      renderSearchResults();
    });
    actions.appendChild(addButton);
    card.appendChild(actions);
    return card;
  }

  function renderSearchResults() {
    searchResults.replaceChildren();
    if (!searchResultsData.length) {
      addMessage(searchResults, search.value.trim() ? 'ไม่พบ Product ID นี้' : 'กรอก Product ID เพื่อเริ่มค้นหา');
      return;
    }
    searchResultsData.forEach((row, index) => searchResults.appendChild(createResultCard(row, index)));
  }

  function renderQueue() {
    selectedLabels.replaceChildren();
    selectedCount.textContent = selectedItems.length.toLocaleString();
    printSelected.disabled = selectedItems.length === 0;
    clearQueue.disabled = selectedItems.length === 0;

    if (!selectedItems.length) {
      addMessage(selectedLabels, 'ยังไม่มีรายการในคิวพิมพ์');
      return;
    }

    selectedItems.forEach((row, index) => {
      const card = document.createElement('article');
      card.className = 'item queue-item';
      card.dataset.queueIndex = String(index);
      const head = document.createElement('div');
      head.className = 'item-head';
      const number = document.createElement('span');
      number.className = 'item-index';
      number.textContent = 'รายการ ' + String(index + 1).padStart(2, '0');
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'remove-button';
      removeButton.textContent = 'ลบรายการ';
      removeButton.setAttribute('aria-label', 'ลบรายการที่ ' + (index + 1));
      removeButton.addEventListener('click', () => {
        selectedItems.splice(index, 1);
        renderQueue();
        renderSearchResults();
      });
      head.append(number, removeButton);
      card.append(head, createLabelPreview(row));
      selectedLabels.appendChild(card);
    });
  }

  function printQueue() {
    if (!selectedItems.length) return;
    document.querySelectorAll('.queue-item').forEach(card => card.classList.add('print-target'));
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

  async function searchByProductID() {
    const requestId = ++searchRequest;
    const productID = search.value.trim();
    if (!productID) {
      searchResultsData = [];
      renderSearchResults();
      count.textContent = 'พร้อมค้นหา';
      updated.textContent = 'ล้างคำค้นหาแล้ว';
      return;
    }

    addMessage(searchResults, 'กำลังค้นหา Product ID…');
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
      addMessage(searchResults, error.message, true);
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
  clearQueue.addEventListener('click', () => {
    selectedItems = [];
    renderQueue();
    renderSearchResults();
  });
  printSelected.addEventListener('click', printQueue);

  renderQueue();
  renderSearchResults();
  checkConnection();
})();