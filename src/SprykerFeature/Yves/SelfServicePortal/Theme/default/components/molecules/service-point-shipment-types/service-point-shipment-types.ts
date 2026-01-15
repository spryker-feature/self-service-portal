import Component from 'ShopUi/models/component';
import { EVENT_FETCHED } from 'ShopUi/components/molecules/ajax-provider/ajax-provider';

export const EVENT_SHIPMENT_TYPE_CHANGE = 'shipment-type-change';

export default class ServicePointShipmentTypes extends Component {
    protected selectedType = null;

    protected readyCallback(): void {}
    protected init(): void {
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.selectedType = this.querySelector<HTMLInputElement>(`.${this.jsName}__radio input:checked`)?.value;

        for (const radio of Array.from(this.querySelectorAll(`.${this.jsName}__radio input`))) {
            radio.addEventListener('change', this.toggle.bind(this));
        }
    }

    protected toggle(event: Event): void {
        const target = event.target as HTMLInputElement;

        if (this.selectedType !== target.value) {
            document.querySelector<HTMLInputElement>(this.getAttribute('service-point-uuid')).value = '';
            this.selectedType = target.value;
        }

        if (this.noServiceTypes.includes(target.value)) {
            event.stopPropagation();
            event.stopImmediatePropagation();
            this.querySelector(`.${this.ajaxContainerClass}`).innerHTML = '';
        }

        this.querySelector('ajax-provider').addEventListener(
            EVENT_FETCHED,
            () => {
                this.dispatchCustomEvent(EVENT_SHIPMENT_TYPE_CHANGE, null, { bubbles: true });
            },
            { once: true },
        );
    }

    protected get ajaxContainerClass(): string {
        return this.getAttribute('ajax-container-class');
    }

    protected get noServiceTypes(): string[] {
        return JSON.parse(this.getAttribute(`no-service-types`));
    }
}
