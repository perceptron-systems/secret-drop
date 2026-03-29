const CIRCUMFERENCE = 2 * Math.PI * 12; // r=12

let ringTimer = null;

export function startRing(durationMs) {
    const ring = document.getElementById('pollRingProgress');
    if (!ring) {
        return;
    }

    if (ringTimer) {
        clearInterval(ringTimer);
    }

    const start = Date.now();
    const tick = () => {
        const elapsed = Date.now() - start;
        const progress = Math.min(elapsed / durationMs, 1);
        ring.style.strokeDashoffset = CIRCUMFERENCE * (1 - progress);
    };

    tick();
    ringTimer = setInterval(tick, 200);
}

export function resetRing() {
    const ring = document.getElementById('pollRingProgress');
    if (ring) {
        ring.style.strokeDashoffset = CIRCUMFERENCE;
    }
    if (ringTimer) {
        clearInterval(ringTimer);
    }
}
