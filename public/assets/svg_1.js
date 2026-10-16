function initializeSVGControls(svgElement, svgContainer, resetButton) {
    let scale = 1;
    let translateX = 0, translateY = 0;
    let startX, startY, isDragging = false;
    const zoomSpeed = 0.1;
    const publicSiteplan = svgContainer.dataset.fitSiteplan === 'true';
    if (publicSiteplan) svgElement.style.transformOrigin = '0 0';

    function zoomAt(nextScale, x, y) {
        nextScale = Math.max(1, Math.min(12, nextScale));
        const ratio = nextScale / scale;
        translateX = x - (x - translateX) * ratio;
        translateY = y - (y - translateY) * ratio;
        scale = nextScale;
    }

    if (publicSiteplan) {
        let gesture = null;
        let suppressClickUntil = 0;
        function touchState(touches) {
            const rect = svgContainer.getBoundingClientRect();
            const a = touches[0], b = touches[1] || a;
            return { x: (a.clientX + b.clientX) / 2 - rect.left,
                y: (a.clientY + b.clientY) / 2 - rect.top,
                distance: Math.hypot(b.clientX - a.clientX, b.clientY - a.clientY), count: touches.length };
        }
        svgElement.addEventListener('touchstart', e => {
            gesture = touchState(e.touches);
            if (e.touches.length > 1) { e.preventDefault(); suppressClickUntil = Date.now() + 500; }
        }, { passive: false });
        svgElement.addEventListener('touchmove', e => {
            if (!gesture) return;
            e.preventDefault();
            const next = touchState(e.touches);
            if (gesture.count === next.count) {
                if (next.count > 1 && gesture.distance > 0) zoomAt(scale * next.distance / gesture.distance, gesture.x, gesture.y);
                translateX += next.x - gesture.x;
                translateY += next.y - gesture.y;
                if (Math.hypot(next.x - gesture.x, next.y - gesture.y) > 2 || next.count > 1) suppressClickUntil = Date.now() + 500;
                applyTransform();
            }
            gesture = next;
        }, { passive: false });
        const endTouch = e => { gesture = e.touches.length ? touchState(e.touches) : null; };
        svgElement.addEventListener('touchend', endTouch);
        svgElement.addEventListener('touchcancel', endTouch);
        svgElement.addEventListener('click', e => {
            if (Date.now() < suppressClickUntil) { e.preventDefault(); e.stopImmediatePropagation(); }
        }, true);
    }

    // Fit the actual drawing; raster backgrounds must not determine its bounds.
    function fitSiteplan() {
        if (svgContainer.dataset.fitSiteplan !== 'true') return;
        const rootMatrix = svgElement.getScreenCTM();
        if (!rootMatrix || !svgContainer.clientWidth) return;
        const inverse = rootMatrix.inverse();
        let left = Infinity, top = Infinity, right = -Infinity, bottom = -Infinity;
        svgElement.querySelectorAll('path, polygon, polyline, rect, circle, ellipse, line, text').forEach(element => {
            if (element.closest('defs, clipPath, mask, pattern, symbol')) return;
            if (getComputedStyle(element).display === 'none') return;
            const box = element.getBBox();
            const matrix = element.getScreenCTM();
            if (!matrix || (!box.width && !box.height)) return;
            const relative = inverse.multiply(matrix);
            [[box.x, box.y], [box.x + box.width, box.y],
                [box.x, box.y + box.height], [box.x + box.width, box.y + box.height]].forEach(([x, y]) => {
                const point = svgElement.createSVGPoint();
                point.x = x;
                point.y = y;
                const mapped = point.matrixTransform(relative);
                left = Math.min(left, mapped.x);
                top = Math.min(top, mapped.y);
                right = Math.max(right, mapped.x);
                bottom = Math.max(bottom, mapped.y);
            });
        });
        if (!Number.isFinite(left) || right <= left || bottom <= top) return;
        const padding = Math.max(right - left, bottom - top) * 0.04;
        svgElement.setAttribute('viewBox', `${left - padding} ${top - padding} ${right - left + padding * 2} ${bottom - top + padding * 2}`);
        svgElement.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    }

    fitSiteplan();
    if (svgContainer.dataset.fitSiteplan === 'true') {
        new ResizeObserver(() => {
            if (scale === 1 && translateX === 0 && translateY === 0) fitSiteplan();
        }).observe(svgContainer);
    }

    svgElement.addEventListener('wheel', function (e) {
        e.preventDefault();
        if (publicSiteplan) {
            const rect = svgContainer.getBoundingClientRect();
            zoomAt(scale + (e.deltaY < 0 ? zoomSpeed : -zoomSpeed), e.clientX - rect.left, e.clientY - rect.top);
            applyTransform();
            return;
        }
        const oldScale = scale;
        scale += (e.deltaY < 0 ? zoomSpeed : -zoomSpeed);
        scale = Math.max(1, scale);

        const containerRect = svgContainer.getBoundingClientRect();
        const centerX = e.clientX - containerRect.left;
        const centerY = e.clientY - containerRect.top;

        const deltaX = (centerX - translateX) * (1 - oldScale / scale);
        const deltaY = (centerY - translateY) * (1 - oldScale / scale);

        translateX -= deltaX;
        translateY -= deltaY;

        applyTransform();
    });

    svgElement.addEventListener('mousedown', function (e) {
        e.preventDefault();
        isDragging = true;
        const containerRect = svgContainer.getBoundingClientRect();
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        svgElement.style.cursor = 'grabbing';
    });

    window.addEventListener('mousemove', function (e) {
        if (isDragging) {
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            applyTransform();
        }
    });

    window.addEventListener('mouseup', function () {
        isDragging = false;
        svgElement.style.cursor = 'grab';
    });

    resetButton.addEventListener('click', function () {
        scale = 1;
        translateX = 0;
        translateY = 0;
        applyTransform();
        fitSiteplan();
    });

    function applyTransform() {
        svgElement.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    }
}

// Loop semua container & SVG secara otomatis
document.querySelectorAll('.svg-container').forEach(container => {
    const svgElement = container.querySelector('svg');
    const resetButton = container.querySelector('.reset-button');
    if (svgElement && resetButton) {
        initializeSVGControls(svgElement, container, resetButton);
    }
});
