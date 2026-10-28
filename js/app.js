(() => {
      const labels = document.getElementById('labels');
      const search = document.getElementById('search');
      const refresh = document.getElementById('refresh');
      const selectAll = document.getElementById('selectAll');
      const printSelected = document.getElementById('printSelected');
      const connection = document.getElementById('connection');
      const totalCount = document.getElementById('totalCount');
      const visibleCount = document.getElementById('visibleCount');
      const selectedCount = document.getElementById('selectedCount');
      const count = document.getElementById('count');
      const updated = document.getElementById('updated');
      let records = [];
      let allRecords = [];
      let searchTimer = null;
      let searchRequest = 0;
      const selected = new Set();

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
          location: field(row, ['location', 'loc', 'locationcode', 'warehouseLocation', 'binLocation', 'whLocation']),
          productID: field(row, ['productID', 'productId', 'productCode', 'itemCode', 'sku', 'partNo', 'product']),
          size1: valueOf(row, ['size', 'size1', 'length', 'productLength', 'dim1']),
          size2: valueOf(row, ['size2', 'width', 'productWidth', 'dim2']),
          size3: valueOf(row, ['size3', 'height', 'productHeight', 'dim3']),
          description: field(row, ['description', 'productDescription', 'itemDescription', 'productName', 'name']),
          barcode: field(row, ['barcode', 'barCode', 'barcodeNo', 'barcodeValue', 'ean', 'upc'])
        };
      }
      function makeLine(className, label, text) {
        const line = document.createElement('div'); line.className = 'label-line ' + className;
        const caption = document.createElement('span'); caption.className = 'field-name'; caption.textContent = label;
        line.appendChild(caption); line.appendChild(document.createTextNode(text)); return line;
      }
      function makeLocationLine(location) {
        const line = document.createElement('div'); line.className = 'label-line line-1';
        line.appendChild(document.createTextNode('form '));
        const locationElement = document.createElement('strong');
        locationElement.textContent = location;
        line.appendChild(locationElement);
        return line;
      }
      function createCard(row, index) {
        const card = document.createElement('article'); card.className = 'item'; card.dataset.index = String(index);
        const head = document.createElement('div'); head.className = 'item-head';
        const selectLabel = document.createElement('label'); selectLabel.className = 'select-label';
        const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.checked = selected.has(index); checkbox.setAttribute('aria-label', 'เลือกฉลาก ' + (index + 1));
        checkbox.addEventListener('change', () => { checkbox.checked ? selected.add(index) : selected.delete(index); updateSelectedCount(); });
        selectLabel.append(checkbox, document.createTextNode('เลือกพิมพ์'));
        const number = document.createElement('span'); number.className = 'item-index'; number.textContent = 'LABEL ' + String(index + 1).padStart(3, '0');
        head.append(selectLabel, number);
        const data = labelData(row);
        const preview = document.createElement('div'); preview.className = 'label-preview';
        preview.appendChild(makeLocationLine(data.location));
        const productLine = document.createElement('div');
        productLine.className = 'label-line line-2';

        const productElement = document.createElement('span');
        productElement.className = 'productID';
        productElement.textContent = data.productID === '—' ? '-' : data.productID;

        const sizeElement = document.createElement('span');
        sizeElement.className = 'psize';
        sizeElement.textContent = [data.size1, data.size2, data.size3]
          .map(size => size && size !== '—' ? size : '9')
          .join(' X ');

        productLine.append(productElement, sizeElement);
        preview.appendChild(productLine);
        preview.appendChild(makeLine('line-3', '', data.description));
        preview.appendChild(makeLine('line-4', '', data.barcode));
        const actions = document.createElement('div'); actions.className = 'item-actions';
        const printButton = document.createElement('button'); printButton.type = 'button'; printButton.textContent = 'พิมพ์ฉลากนี้';
        printButton.addEventListener('click', () => printCards([index]));
        actions.appendChild(printButton);
        card.append(head, preview, actions);
        return card;
      }
      function filteredIndexes() {
        const term = search.value.trim().toLocaleLowerCase();
        return records.map((row, index) => ({ row, index })).filter(({ row }) => !term || Object.values(row).some(value => String(value ?? '').toLocaleLowerCase().includes(term))).map(({ index }) => index);
      }
      function render() {
        const indexes = filteredIndexes();
        visibleCount.textContent = indexes.length.toLocaleString();
        count.textContent = indexes.length.toLocaleString() + ' / ' + records.length.toLocaleString() + ' รายการ';
        labels.replaceChildren();
        if (!indexes.length) {
          const empty = document.createElement('div'); empty.className = 'empty';
          empty.textContent = search.value.trim() ? 'ไม่พบ productID ที่ตรงกับคำค้นหา' : 'ยังไม่มีข้อมูลในตาราง';
          labels.appendChild(empty);
        } else indexes.forEach(index => labels.appendChild(createCard(records[index], index)));
        updateSelectedCount();
      }
      function updateSelectedCount() {
        selectedCount.textContent = selected.size.toLocaleString();
        printSelected.disabled = selected.size === 0;
        selectAll.textContent = filteredIndexes().every(index => selected.has(index)) && filteredIndexes().length ? 'ยกเลิกเลือก' : 'เลือกทั้งหมด';
      }
      function printCards(indexes) {
        if (!indexes.length) return;
        document.querySelectorAll('.item').forEach(card => {
          card.classList.toggle('print-target', indexes.includes(Number(card.dataset.index)));
        });
        window.print();
      }
      window.addEventListener('afterprint', () => document.querySelectorAll('.item').forEach(card => card.classList.remove('print-target')));
      async function load() {
        refresh.disabled = true; refresh.textContent = 'กำลังโหลด…';
        try {
          const [healthResponse, dataResponse] = await Promise.all([fetch('/api/health'), fetch('/api/labels')]);
          if (healthResponse.ok) { connection.textContent = '● SQL Server เชื่อมต่อแล้ว'; connection.style.background = '#e1f8eb'; connection.style.color = '#17683a'; }
          else { connection.textContent = 'SQL Server มีปัญหา'; connection.style.background = '#fee4e2'; connection.style.color = '#b42318'; }
          const payload = await dataResponse.json();
          if (!dataResponse.ok) throw new Error(payload.error || 'โหลดข้อมูลไม่สำเร็จ');
          allRecords = Array.isArray(payload.data) ? payload.data : [];
          records = allRecords;
          selected.clear(); totalCount.textContent = allRecords.length.toLocaleString(); render();
          updated.textContent = 'อัปเดต ' + new Date().toLocaleString('th-TH');
        } catch (error) {
          connection.textContent = 'เชื่อมต่อ API ไม่สำเร็จ'; connection.style.background = '#fee4e2'; connection.style.color = '#b42318';
          labels.replaceChildren(); const message = document.createElement('div'); message.className = 'empty error';
          message.textContent = error.message + ' — ตรวจสอบการตั้งค่า DB และลองรีเฟรช'; labels.appendChild(message);
        } finally { refresh.disabled = false; refresh.textContent = '↻ รีเฟรช'; }
      }

      async function searchByProductID() {
        const requestId = ++searchRequest;
        const productID = search.value.trim();
        if (!productID) {
          records = allRecords;
          selected.clear();
          render();
          return;
        }

        labels.replaceChildren();
        const loading = document.createElement('div');
        loading.className = 'empty';
        loading.textContent = 'กำลังค้นหา productID…';
        labels.appendChild(loading);

        try {
          const response = await fetch('/api/labels/search?productID=' + encodeURIComponent(productID));
          const payload = await response.json();
          if (requestId !== searchRequest) return;
          if (response.status === 404) {
            records = [];
          } else if (!response.ok) {
            throw new Error(payload.error || 'ค้นหาข้อมูลไม่สำเร็จ');
          } else {
            records = Array.isArray(payload.data) ? payload.data : [];
          }
          selected.clear();
          render();
          updated.textContent = 'ค้นหา productID: ' + productID + ' · ' + new Date().toLocaleString('th-TH');
        } catch (error) {
          if (requestId !== searchRequest) return;
          labels.replaceChildren();
          const message = document.createElement('div');
          message.className = 'empty error';
          message.textContent = error.message;
          labels.appendChild(message);
        }
      }
      search.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        clearTimeout(searchTimer);
        searchByProductID();
      });
      refresh.addEventListener('click', load);
      selectAll.addEventListener('click', () => {
        const indexes = filteredIndexes();
        const allSelected = indexes.length > 0 && indexes.every(index => selected.has(index));
        indexes.forEach(index => allSelected ? selected.delete(index) : selected.add(index));
        render();
      });
      printSelected.addEventListener('click', () => printCards([...selected]));
      load();
    })();
