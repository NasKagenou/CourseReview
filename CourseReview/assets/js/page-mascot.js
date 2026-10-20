// คลาสนี้ควบคุมรูปมาสคอตหนึ่งตัว: สลับภาพตามตำแหน่งเมาส์และตอบสนองเมื่อคลิก
class PageMascot {
    constructor(element) {
        // เก็บปุ่มมาสคอตและค่ารูป sprite ที่ navbar ส่งผ่าน data attributes
        this.element = element;
        this.image = element.querySelector('.page-mascot__image');
        this.lookSheet = element.dataset.look;
        this.reactionSheet = element.dataset.react;
        this.look = { column: 1, row: 1 };
        this.reactionTimer = null;

        // ตำแหน่งของสีหน้าต่าง ๆ ในภาพ sprite แบบ 3 คอลัมน์ 3 แถว
        this.reactions = [
            { column: 1, row: 0 },
            { column: 2, row: 0 },
            { column: 0, row: 1 },
            { column: 1, row: 1 },
            { column: 2, row: 1 },
            { column: 2, row: 2 }
        ];
    }

    initialize() {
        // เริ่มจากภาพมองตรง แล้วผูกเหตุการณ์เมาส์และคลิกเข้ากับเมธอดของคลาส
        this.showSprite(this.lookSheet, this.look);
        document.addEventListener('mousemove', this.handleMouseMove.bind(this));
        this.element.addEventListener('click', this.handleClick.bind(this));
    }

    showSprite(sheet, position) {
        // เลือกไฟล์ภาพและช่อง sprite ตามคอลัมน์/แถวที่รับเข้ามา
        this.image.style.backgroundImage = `url("${sheet}")`;
        this.image.style.backgroundPosition = `${position.column * 50}% ${position.row * 50}%`;
    }

    getDirection(distance, size) {
        // แปลงระยะจากกึ่งกลางมาสคอตเป็นซ้าย/กลาง/ขวา หรือ บน/กลาง/ล่าง
        const relativeDistance = distance / size;
        if (relativeDistance < -0.25) return 0;
        if (relativeDistance > 0.25) return 2;
        return 1;
    }

    handleMouseMove(event) {
        // ไม่เปลี่ยนภาพสายตาระหว่างกำลังแสดงสีหน้าตอบสนองต่อการคลิก
        if (this.element.classList.contains('is-reacting')) return;

        const bounds = this.element.getBoundingClientRect();
        const centerX = bounds.left + bounds.width / 2;
        const centerY = bounds.top + bounds.height / 2;

        this.look = {
            column: this.getDirection(event.clientX - centerX, bounds.width),
            row: this.getDirection(event.clientY - centerY, bounds.height)
        };
        this.showSprite(this.lookSheet, this.look);
    }

    handleClick() {
        // ยกเลิกตัวจับเวลาเก่า แล้วสุ่มแสดงสีหน้าหนึ่งภาพเป็นเวลา 0.8 วินาที
        window.clearTimeout(this.reactionTimer);

        const index = Math.floor(Math.random() * this.reactions.length);
        this.showSprite(this.reactionSheet, this.reactions[index]);
        this.element.classList.add('is-reacting');

        this.reactionTimer = window.setTimeout(() => {
            this.element.classList.remove('is-reacting');
            this.showSprite(this.lookSheet, this.look);
        }, 800);
    }
}

// สร้างตัวควบคุมเฉพาะเมื่อหน้าปัจจุบันมีปุ่มมาสคอตใน navbar
const mascotElement = document.getElementById('page-mascot');
if (mascotElement) {
    new PageMascot(mascotElement).initialize();
}
