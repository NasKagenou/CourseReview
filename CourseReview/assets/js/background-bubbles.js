// สร้างวงแสงพื้นหลังหลังจากเบราว์เซอร์เตรียมโครงสร้างหน้าเว็บแล้ว
document.addEventListener('DOMContentLoaded', function () {
    const container = document.createElement('div');
    container.className = 'bubbles-container';
    document.body.appendChild(container);

    // รูปแบบแสง 3 สีที่สุ่มใช้กับวงแสงแต่ละวง
    const glowColors = [
        'box-shadow: 0 0 50px 20px rgba(99, 102, 241, 0.7); background: rgba(99, 102, 241, 0.5); border: none;',
        'box-shadow: 0 0 50px 20px rgba(217, 70, 239, 0.7); background: rgba(217, 70, 239, 0.5); border: none;',
        'box-shadow: 0 0 50px 20px rgba(236, 72, 153, 0.7); background: rgba(236, 72, 153, 0.5); border: none;'
    ];

    // สร้างวงแสง 35 วง โดยสุ่มขนาด ตำแหน่ง สี และความเร็วในการลอย
    for (let i = 0; i < 35; i++) {
        const bubble = document.createElement('div');
        bubble.className = 'floating-bubble';

        const size = Math.random() * 60 + 20;
        const left = Math.random() * 100;
        const duration = Math.random() * 6 + 5;
        const delay = Math.random() * 2;
        const color = glowColors[Math.floor(Math.random() * glowColors.length)];

        bubble.style.width = size + 'px';
        bubble.style.height = size + 'px';
        bubble.style.left = left + 'vw';
        bubble.style.animationDuration = duration + 's';
        bubble.style.animationDelay = delay + 's';
        bubble.style.cssText += color;

        container.appendChild(bubble);
    }
});
