'use strict';

function Select(Alpine) {
    require('Vendor/tg666/dependent-select-box/client-side/dependentSelectBox');

    let watchedComponentIds = [];
    let autoincrement = 0;
    const isTouchScreen = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);

    window.addEventListener('resize', () => {
        watchedComponentIds.forEach(cid => {
            Alpine.store(cid).recalculateOptionsPosition();
        });
    });

    Alpine.data('select', () => ({
        cid: null,
        select: {
            ['x-init']() {
                this.cid = this.$el.getAttribute('data-cid');

                this.$watch(`$store.${this.cid}.opened`, (() => {
                    this.$refs.button.focus();
                }));

                const selectId = this.$store[this.cid].el.getAttribute('id');

                if (selectId) {
                    document.querySelectorAll(`label[for="${selectId}"]`).forEach(label => {
                        label.addEventListener('click', () => {
                            this.$refs.button.focus();
                        });
                    });
                }
            },
        },
        selectButton: {
            ['x-ref']: 'button',
            [':aria-haspopup']() {
                return 'listbox';
            },
            [':aria-expanded']() {
                return this.$store[this.cid].opened;
            },
            [':disabled']() {
                return this.$store[this.cid].disabled;
            },
            ['x-on:click']() {
                if (!this.$event.target.hasAttribute('data-remove-button')) {
                    this.$store[this.cid].toggle();
                }
            },
        },
    }));

    Alpine.data('selectOptions', () => ({
        cid: null,
        focusItem(index) {
            const item = this.$el.querySelectorAll('ul > li')[index];

            if (!item) {
                return null;
            }

            item.scrollIntoView({
                block: 'nearest'
            });

            if (!isTouchScreen) {
                item.getElementsByTagName('button')[0].focus();
            }

            return item;
        },
        matchVisibleItems(search) {
            search = search.trim();

            for (let i in this.$store[this.cid].options) {
                const option = this.$store[this.cid].options[i];

                option.visible = (!search.length || -1 !== option.label.search(new RegExp(search, 'i')));
            }
        },
        selectOptions: {
            ['x-init']() {
                this.cid = this.$el.getAttribute('data-cid');

                this.$watch(`$store.${this.cid}.activeIndex`, (() => {
                    if (!this.$store[this.cid].opened) {
                        return;
                    }

                    if (null === this.$store[this.cid].activeIndex) {
                        this.$store[this.cid].activeDescendant = '';

                        return;
                    }

                    const item = this.focusItem(this.$store[this.cid].activeIndex);

                    if (item) {
                        this.$store[this.cid].activeDescendant = item.id;
                    }
                }));

                this.$watch(`$store.${this.cid}.selected`, (() => {
                    if (!this.$store[this.cid].multiple) {
                        return;
                    }

                    this.$nextTick((() => {
                        this.$store[this.cid].recalculateOptionsPosition();

                        if (null !== this.$store[this.cid].activeIndex) {
                            this.focusItem(this.$store[this.cid].activeIndex);

                            return;
                        }

                        if (this.$refs.searchbar) {
                            this.$refs.searchbar.focus();
                        } else {
                            this.$el.focus();
                        }
                    }));
                }));

                this.$watch(`$store.${this.cid}.opened`, ((opened) => {
                    if (!opened) {
                        this.$nextTick((() => {
                            this.$store[this.cid].searchbarValue = '';
                        }));

                        return;
                    }

                    this.$nextTick((() => {
                        this.$el.focus();
                        this.$el.getElementsByTagName('ul')[0].scrollTo(0, 0);

                        if (this.$refs.searchbar) {
                            this.$refs.searchbar.focus();
                        }
                    }));
                }));

                this.$watch(`$store.${this.cid}.searchbarValue`, ((searchbarValue) => {
                    this.matchVisibleItems(searchbarValue);
                }));
            },
            ['x-transition:enter']: '',
            ['x-transition:enter-start']: '',
            ['x-transition:enter-end']: '',
            ['x-transition:leave']: '',
            ['x-transition:leave-start']: '',
            ['x-transition:leave-end']: '',
            ['x-on:click.outside']() {
                if (!this.$event.target.closest(`[data-cid="${this.cid}"]`)) {
                    this.$store[this.cid].close();
                }
            },
            ['x-on:keydown.escape.prevent.stop']() {
                this.$store[this.cid].close();
            },
            ['x-on:focusin.window']() {
                !this.$el.contains(this.$event.target)
                && !this.$event.target.closest(`[data-cid="${this.cid}"]`)
                && this.$store[this.cid].close();
            },
            [':aria-activedescendant']() {
                return this.cid ? this.$store[this.cid].activeDescendant : '';
            },
            ['x-show']() {
                return this.$store[this.cid].opened;
            },
            ['x-on:keydown.up.prevent.stop']() {
                const options = this.$store[this.cid].options;

                let index = this.$store[this.cid].activeIndex;

                if (null === index || (!options[index] || !options[index].visible)) {
                    this.$store[this.cid].activeIndex = null;

                    return;
                }

                let prevIndex = null;

                for (let i in options) {
                    i = parseInt(i);
                    const option = options[i];

                    if (!option.visible) {
                        continue;
                    }

                    if (index === i) {
                        index = prevIndex;

                        break;
                    }

                    prevIndex = i;
                }

                if (index !== null) {
                    this.$store[this.cid].activeIndex = index;
                } else if (this.$refs.searchbar) {
                    this.$store[this.cid].activeIndex = index;
                    this.$refs.searchbar.focus();
                }
            },
            ['x-on:keydown.down.prevent.stop']() {
                const options = this.$store[this.cid].options;
                let index = this.$store[this.cid].activeIndex;

                if (null !== index && (!options[index] || !options[index].visible)) {
                    index = null;
                }

                let currentFound = null === index;

                for (let i in options) {
                    i = parseInt(i);
                    const option = options[i];

                    if (!option.visible) {
                        continue;
                    }

                    if (currentFound) {
                        index = i;

                        break;
                    }

                    if (index === i) {
                        currentFound = true;
                    }
                }

                this.$store[this.cid].activeIndex = index;
            },
            ['x-id']: '["selectOptions"]',
        },
    }));

    Alpine.directive('select', (el, {modifiers}, { cleanup }) => {
        autoincrement++;
        const cid = 'selectPluginState_' + autoincrement;
        let searchbar = '';
        let buttonText;

        if (-1 !== modifiers.indexOf('tags') && el.multiple) {
            buttonText = `
                <span class="flex flex-wrap -mb-2.5 sm:-mb-1.5">
                    <template x-for="(option, index) in $store.${cid}.options" :key="option.value">
                        <span x-show="$store.${cid}.isSelected(index)" class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-indigo-100 text-indigo-800 mr-1.5 mb-1.5 h-full">
                            <span x-html="option.selectedHtml"></span>
                            <button type="button" data-remove-button class="r-0.5 pl-1.5" x-on:click="$store.${cid}.choose(index)">
                                &times;
                            </button>
                        </span>
                    </template>
                    <span class="mb-1.5 py-0.5 h-full">&nbsp;</span>
                </span>
            `;
        } else {
            buttonText = `<span class="block truncate" x-html="$store.${cid}.selectText || '&nbsp'"></span>`;
        }

        if (-1 !== modifiers.indexOf('searchbar')) {
            searchbar = `
                <div class="select-none relative py-2 px-3">
                    <input x-ref="searchbar" x-model="$store.${cid}.searchbarValue" type="text" placeholder="..." class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-sm border-gray-300 rounded-md disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 disabled:shadow-none">
                </div>
            `;
        }

        const selectEl = document.createElement('div');
        selectEl.innerHTML = `
            <button x-bind="selectButton" type="button" class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 disabled:shadow-none">
                ${buttonText}
                <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                    <span class="h-5 w-5 text-gray-400">
                        ${require('../../images/icon/common/chevron-down.svg')}
                    </span>
                </span>
            </button>
        `;

        selectEl.setAttribute('x-data', 'select');
        selectEl.setAttribute('x-bind', 'select');
        selectEl.setAttribute('data-cid', cid);
        selectEl.setAttribute('class', 'relative w-full font-normal');

        const optionsEl = document.createElement('div');
        optionsEl.innerHTML = `
            <div class="flex flex-col pointer-events-auto bg-white shadow-lg rounded-md py-1 ring-1 ring-black ring-opacity-5">
                ${searchbar}
                <ul class="max-h-60 overflow-auto" role="listbox">
                    <template x-for="(option, index) in $store.${cid}.options" :key="option.value">
                        <li x-show="option.visible" :id="$id('selectOptions') + index" class="flex mx-1" role="option">
                            <button x-on:click="$store.${cid}.choose(index)" x-on:mouseenter="$store.${cid}.activeIndex = index" class="group w-full cursor-pointer select-none relative py-2 pl-3 pr-9 rounded text-gray-900 focus:text-white focus:bg-indigo-600 focus:outline-none">
                                <span x-html="option.html" class="text-left font-normal block truncate"></span>
        
                                <span x-show="$store.${cid}.isSelected(index)" class="absolute inset-y-0 right-0 flex items-center pr-2 text-indigo-600 text-indigo-600 group-focus:text-white">
                                    <span class="h-5 w-5">
                                        ${require('../../images/icon/common/check-2.svg')}
                                    </span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        `;

        optionsEl.setAttribute('x-data', 'selectOptions');
        optionsEl.setAttribute('x-bind', 'selectOptions');
        optionsEl.setAttribute('data-cid', cid);
        optionsEl.setAttribute('class', 'absolute flex flex-col justify-start pointer-events-none mt-1 w-auto text-base focus:outline-none sm:text-sm z-30');
        optionsEl.setAttribute('tabindex', '-1');

        const containerEl = el.closest('[data-plugin-container]') || null;

        Alpine.store(cid, {
            cid: cid,
            el: el,
            selectEl: selectEl,
            optionsEl: optionsEl,
            containerEl: containerEl,
            _optionsBindTo: null,
            opened: false,
            disabled: el.disabled || false,
            multiple: el.multiple || false,
            selected: [],
            options: [],
            selectText: '',
            activeIndex: null,
            activeDescendant: '',
            searchbarValue: '',

            init() {
                const buildOptions = () => {
                    this.selected = [];
                    this.options = [];

                    for (let opt of this.el.options) {
                        const option = {
                            value: opt.value,
                            label: opt.innerText,
                            html: opt.hasAttribute('data-html') ? opt.getAttribute('data-html') : opt.innerText,
                            visible: true,
                        };

                        option.selectedHtml = opt.hasAttribute('data-selected-html') ? opt.getAttribute('data-selected-html') : option.html;

                        this.options.push(option);

                        if (opt.selected) {
                            this.selected.push(this.options.length -1);
                        }
                    }

                    this.updateSelectText();
                };

                buildOptions();

                // dependent select box
                if (this.el.hasAttribute('data-dependentselectbox')) {
                    $(this.el).dependentSelectBox(buildOptions);
                }
            },

            isSelected(index) {
                return -1 !== this.selected.indexOf(index);
            },

            recalculateOptionsPosition() {
                const selectBounds = this.selectEl.getBoundingClientRect();
                const scrollY = this.containerEl ? this.containerEl.scrollTop : window.scrollY;
                const scrollX = this.containerEl ? this.containerEl.scrollLeft : window.scrollX;
                let selectTop = selectBounds.top;
                let selectLeft = selectBounds.left;

                if (this.containerEl) {
                    const containerBounds = this.containerEl.getBoundingClientRect();
                    selectTop -= containerBounds.top;
                    selectLeft -= containerBounds.left;
                }

                let left = Math.round(scrollX + selectLeft);
                let right = null;
                let top = Math.round(scrollY + selectTop + this.selectEl.offsetHeight);
                let flexDirection = 'column';
                let justifyContent = 'flex-start';

                const optionsVisibility = this.optionsEl.style.visibility;
                const optionsDisplay = this.optionsEl.style.display;
                const documentHeight = this.containerEl ? this.containerEl.scrollHeight : document.documentElement.scrollHeight;
                const documentWidth = this.containerEl ? this.containerEl.scrollWidth : document.documentElement.scrollWidth;

                this.optionsEl.style.visibility = 'hidden';
                this.optionsEl.style.display = 'block';
                this.optionsEl.style.height = 'auto';

                if ((documentHeight - top) < (this.optionsEl.offsetHeight + 10)) {
                    top = top - this.optionsEl.offsetHeight - this.selectEl.offsetHeight - (4 * 2);
                    flexDirection = 'column-reverse';
                    justifyContent = 'flex-end';
                }

                const minWidth = this.selectEl.offsetWidth;
                let maxWidth = (documentWidth - left - 3);

                if (minWidth > maxWidth) {
                    maxWidth = (documentWidth - 3);
                    right = documentWidth - (left + minWidth)
                    right = right < 0 ? 3 : right;
                    left = null;
                }

                const height = this.optionsEl.offsetHeight;

                this.optionsEl.style.visibility = optionsVisibility;
                this.optionsEl.style.display = optionsDisplay;

                this.optionsEl.style.position = 'absolute';
                this.optionsEl.style.minWidth = (minWidth < maxWidth ? minWidth : maxWidth) + 'px';
                this.optionsEl.style.maxWidth = maxWidth + 'px';
                this.optionsEl.style.left = null !== left ? (left + 'px') : 'auto';
                this.optionsEl.style.right = null !== right ? (right + 'px') : 'auto';
                this.optionsEl.style.top = top + 'px';
                this.optionsEl.style.height = height + 'px';
                this.optionsEl.style.justifyContent = justifyContent;

                this.optionsEl.children[0].style.flexDirection = flexDirection;
            },

            toggle() {
                if (this.opened) {
                    return this.close();
                }

                this.recalculateOptionsPosition();

                this.opened = true;
            },

            close(focusAfter) {
                if (!this.opened) {
                    return;
                }

                this.opened = false;
                this.activeIndex = null;

                focusAfter && focusAfter.focus();
            },

            choose(index) {
                if (!this.multiple) {
                    this.selected = [index];
                    this.updateSelectText();
                    this.updateSelectValue();
                    this.close();

                    return;
                }

                const selected = this.selected;
                const indexOfIndex = selected.indexOf(index);

                if (-1 === indexOfIndex) {
                    selected.push(index);
                } else {
                    delete selected[indexOfIndex];
                }

                this.selected = selected.sort();
                this.updateSelectText();
                this.updateSelectValue();
            },

            updateSelectText() {
                const labels = [];

                for (let i in this.selected) {
                    labels.push(this.options[this.selected[i]].selectedHtml);
                }

                this.selectText = labels.join(', ');
            },

            updateSelectValue() {
                const values = [];

                for (let i in this.selected) {
                    values.push(this.options[this.selected[i]].value);
                }

                for (let opt of this.el.options) {
                    opt.selected = -1 !== values.indexOf(opt.value);
                }

                this.el.dispatchEvent(new Event('change'));
                $(this.el).trigger('change');
            },

            cleanup() {
                this.selectEl.remove();
                this.optionsEl.remove();
                watchedComponentIds = watchedComponentIds.filter(cid => cid !== this.cid);
            }
        });

        const previousDisplayValue = el.style.display;

        el.style.display = 'none';
        el.insertAdjacentElement('beforebegin', selectEl);

        if (containerEl) {
            containerEl.insertAdjacentElement('beforeend', optionsEl);
        } else {
            document.body.insertAdjacentElement('beforeend', optionsEl);
        }

        watchedComponentIds.push(cid);

        cleanup(() => {
            el.style.display = previousDisplayValue;
            Alpine.store(cid).cleanup();
        });
    });
}

export default Select;
