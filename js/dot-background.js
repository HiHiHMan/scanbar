(() => {
  const canvas = document.getElementById('dotCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d', { alpha: false });
  const spacing = 18;
  const mouse = { x: -1000, y: -1000 };
  const smoothMouse = { x: -1000, y: -1000, radius: 280 };
  const colors = {
    purple: [138, 108, 168],
    cyan: [110, 152, 160],
    green: [120, 158, 138],
    base: [205, 210, 218]
  };

  let cols = 0;
  let rows = 0;
  let dots = [];
  let time = 0;
  let width = 0;
  let height = 0;
  let pixelRatio = 1;

  function resizeCanvas() {
    pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = Math.round(width * pixelRatio);
    canvas.height = Math.round(height * pixelRatio);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);

    cols = Math.ceil(width / spacing) + 2;
    rows = Math.ceil(height / spacing) + 2;
    dots = Array.from({ length: cols }, () =>
      Array.from({ length: rows }, () => ({
        offsetX: 0, offsetY: 0, currentSize: 1.2, opacity: 1
      }))
    );
  }

  function interpolateColor(a, b, factor) {
    return a.map((value, index) => Math.round(value + factor * (b[index] - value)));
  }

  function draw() {
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    time += 0.005;

    smoothMouse.x += (mouse.x - smoothMouse.x) * 0.18;
    smoothMouse.y += (mouse.y - smoothMouse.y) * 0.18;

    for (let i = 0; i < cols; i++) {
      for (let j = 0; j < rows; j++) {
        const baseX = i * spacing;
        const baseY = j * spacing;
        const dot = dots[i][j];
        const dx = baseX - smoothMouse.x;
        const dy = baseY - smoothMouse.y;
        const dist = Math.hypot(dx, dy);

        let targetX = 0;
        let targetY = 0;
        let targetSize = 1.2;
        let targetOpacity = 1;
        let color = colors.base;

        if (dist < smoothMouse.radius) {
          const norm = dist / smoothMouse.radius;
          const factor = Math.pow(1 - norm, 1.8);
          const angle = Math.atan2(dy, dx);
          targetX = Math.cos(angle) * factor * 35
            + Math.sin(time * 1.8 + i * 0.4 + j * 0.6) * 2.4 * factor;
          targetY = Math.sin(angle) * factor * 35
            + Math.cos(time * 1.5 + i * 0.6 + j * 0.4) * 2.4 * factor;
          targetSize = 0.75 + (1.2 - 0.75) * Math.pow(norm, 1.2);
          targetOpacity = 0.6 + 0.4 * Math.pow(norm, 1.5);

          if (norm < 0.25) {
            color = interpolateColor(colors.purple, colors.cyan, norm / 0.25);
          } else if (norm < 0.5) {
            color = interpolateColor(colors.cyan, colors.green, (norm - 0.25) / 0.25);
          } else {
            color = interpolateColor(colors.green, colors.base, Math.pow((norm - 0.5) / 0.5, 1.2));
          }
        }

        const ease = dist < smoothMouse.radius ? 0.03 : 0.005;
        dot.offsetX += (targetX - dot.offsetX) * ease;
        dot.offsetY += (targetY - dot.offsetY) * ease;
        dot.currentSize += (targetSize - dot.currentSize) * ease;
        dot.opacity += (targetOpacity - dot.opacity) * ease;

        ctx.beginPath();
        ctx.arc(baseX + dot.offsetX, baseY + dot.offsetY, Math.max(0.4, dot.currentSize), 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${color[0]}, ${color[1]}, ${color[2]}, ${dot.opacity})`;
        ctx.fill();
      }
    }

    requestAnimationFrame(draw);
  }

  window.addEventListener('resize', resizeCanvas, { passive: true });
  window.addEventListener('mousemove', event => {
    mouse.x = event.clientX;
    mouse.y = event.clientY;
  }, { passive: true });
  window.addEventListener('mouseleave', () => {
    mouse.x = -1000;
    mouse.y = -1000;
  });

  resizeCanvas();
  draw();
})();