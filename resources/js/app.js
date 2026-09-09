let savedKanbanScrollLeft = 0;
let restoringKanbanScroll = false;

function captureKanbanScrollFromDom() {
    const board = document.querySelector('[data-test="kanban-board"]');

    if (board === null) {
        return;
    }

    savedKanbanScrollLeft = board.scrollLeft;
}

function rememberKanbanScrollLeft(left) {
    if (restoringKanbanScroll) {
        return;
    }

    savedKanbanScrollLeft = left;
}

function restoreKanbanScrollPosition() {
    const board = document.querySelector('[data-test="kanban-board"]');
    const topScrollbar = document.querySelector('[data-test="kanban-top-scrollbar"]');

    if (board === null) {
        return;
    }

    board.scrollLeft = savedKanbanScrollLeft;

    if (topScrollbar === null) {
        return;
    }

    topScrollbar.scrollLeft = savedKanbanScrollLeft;
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('kanbanScroller', kanbanScroller);
});

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('commit', (payload) => {
        const succeed = payload.succeed;
        const fail = payload.fail;

        captureKanbanScrollFromDom();
        restoringKanbanScroll = true;

        succeed(() => {
            requestAnimationFrame(() => {
                restoreKanbanScrollPosition();
                requestAnimationFrame(() => {
                    restoreKanbanScrollPosition();
                    window.setTimeout(() => {
                        restoreKanbanScrollPosition();
                        restoringKanbanScroll = false;
                    }, 50);
                });
            });
        });

        fail(() => {
            restoringKanbanScroll = false;
        });
    });
});

function kanbanScroller() {
    return {
        autoscroll: false,
        ignoreMiddleMouseUp: false,
        originX: 0,
        originY: 0,
        pointerX: 0,
        pointerY: 0,
        frameId: null,
        spacerObserver: null,
        restoringScroll: false,

        init() {
            this.restoringScroll = true;
            this.syncSpacerWidth();
            this.applySavedScroll();

            const observer = new ResizeObserver(() => {
                this.syncSpacerWidth();

                if (!this.restoringScroll && !restoringKanbanScroll) {
                    return;
                }

                this.applySavedScroll();
            });
            observer.observe(this.$refs.track);
            this.spacerObserver = observer;

            requestAnimationFrame(() => {
                this.applySavedScroll();
                requestAnimationFrame(() => {
                    this.applySavedScroll();
                    this.restoringScroll = false;
                });
            });
        },

        destroy() {
            this.stopAutoscroll();

            if (this.spacerObserver === null) {
                return;
            }

            this.spacerObserver.disconnect();
            this.spacerObserver = null;
        },

        syncSpacerWidth() {
            const track = this.$refs.track;
            const spacer = this.$refs.topSpacer;
            spacer.style.width = `${track.scrollWidth}px`;
        },

        applySavedScroll() {
            this.$refs.board.scrollLeft = savedKanbanScrollLeft;
            this.$refs.topScrollbar.scrollLeft = savedKanbanScrollLeft;
        },

        syncBoardFromTop() {
            if (this.restoringScroll || restoringKanbanScroll) {
                this.applySavedScroll();
                return;
            }

            this.$refs.board.scrollLeft = this.$refs.topScrollbar.scrollLeft;
            rememberKanbanScrollLeft(this.$refs.board.scrollLeft);
        },

        syncTopFromBoard() {
            if (this.restoringScroll || restoringKanbanScroll) {
                this.applySavedScroll();
                return;
            }

            this.$refs.topScrollbar.scrollLeft = this.$refs.board.scrollLeft;
            rememberKanbanScrollLeft(this.$refs.board.scrollLeft);
        },

        onBoardMouseDown(event) {
            if (event.button !== 1) {
                return;
            }

            event.preventDefault();

            if (this.autoscroll) {
                this.stopAutoscroll();
                return;
            }

            this.startAutoscroll(event);
        },

        startAutoscroll(event) {
            this.autoscroll = true;
            this.ignoreMiddleMouseUp = true;
            this.originX = event.clientX;
            this.originY = event.clientY;
            this.pointerX = event.clientX;
            this.pointerY = event.clientY;
            this.tickAutoscroll();
        },

        stopAutoscroll() {
            this.autoscroll = false;
            this.ignoreMiddleMouseUp = false;

            if (this.frameId === null) {
                return;
            }

            cancelAnimationFrame(this.frameId);
            this.frameId = null;
        },

        onWindowMouseMove(event) {
            if (!this.autoscroll) {
                return;
            }

            this.pointerX = event.clientX;
            this.pointerY = event.clientY;
        },

        onWindowMouseUp(event) {
            if (!this.autoscroll) {
                return;
            }

            if (this.ignoreMiddleMouseUp && event.button === 1) {
                this.ignoreMiddleMouseUp = false;
                return;
            }

            this.stopAutoscroll();
        },

        onWindowAuxClick(event) {
            if (event.button !== 1) {
                return;
            }

            event.preventDefault();
        },

        onWindowKeydown(event) {
            if (event.key !== 'Escape') {
                return;
            }

            this.stopAutoscroll();
        },

        tickAutoscroll() {
            if (!this.autoscroll) {
                return;
            }

            const deltaX = this.pointerX - this.originX;
            const deltaY = this.pointerY - this.originY;
            const board = this.$refs.board;
            const speedX = deltaX * 0.08;
            const speedY = deltaY * 0.08;
            const verticalRoot = document.scrollingElement;

            board.scrollLeft = board.scrollLeft + speedX;
            rememberKanbanScrollLeft(board.scrollLeft);
            this.syncTopFromBoard();

            if (verticalRoot !== null) {
                verticalRoot.scrollTop = verticalRoot.scrollTop + speedY;
            }

            this.frameId = requestAnimationFrame(() => {
                this.tickAutoscroll();
            });
        },

        scrollerStyle() {
            return {
                left: `${this.originX}px`,
                top: `${this.originY}px`,
            };
        },
    };
}
