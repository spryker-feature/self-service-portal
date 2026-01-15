import Component from 'ShopUi/models/component';
import MainPopup, { EVENT_CLOSE_POPUP, EVENT_POPUP_OPENED } from 'ShopUi/components/molecules/main-popup/main-popup';
import ServicePointFinder, {
    EVENT_SET_SERVICE_POINT,
    ServicePointEventDetail,
} from 'ServicePointWidget/components/molecules/service-point-finder/service-point-finder';
import { EVENT_SHIPMENT_TYPE_CHANGE } from '../service-point-shipment-types/service-point-shipment-types';

export default class SspServicePointSelector extends Component {
    protected noLocationContainer: HTMLElement;
    protected location: HTMLElement;
    protected locationContainer: HTMLElement;
    protected finder: ServicePointFinder;
    protected popup: MainPopup;

    protected readyCallback(): void {}
    protected init(): void {
        this.noLocationContainer = <HTMLElement>this.getElementsByClassName(`${this.jsName}__no-location`)[0];
        this.location = <HTMLElement>this.getElementsByClassName(`${this.jsName}__location`)[0];
        this.locationContainer = <HTMLElement>this.getElementsByClassName(`${this.jsName}__location-container`)[0];
        this.popup = <MainPopup>this.getElementsByClassName(`${this.jsName}__popup`)[0];

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.popup?.addEventListener(EVENT_POPUP_OPENED, this.mapFinderSetServicePointEvent.bind(this));
        this.changeOffer();
        this.onShipmentTypeChange();
    }

    protected onShipmentTypeChange(): void {
        document.addEventListener(
            EVENT_SHIPMENT_TYPE_CHANGE,
            () => {
                const checkbox =
                    document.querySelector<HTMLInputElement>(`${this.merchantReferenceSelector}:checked`) ??
                    document.querySelector<HTMLInputElement>(`${this.offerReferenceSelector}:checked`);

                checkbox?.dispatchEvent(new Event('change', { bubbles: true }));
            },
            { once: true },
        );
    }

    protected mapFinderSetServicePointEvent(): void {
        if (this.finder) {
            return;
        }

        this.finder = <ServicePointFinder>document.getElementsByClassName(this.finderClassName)[0];

        if (this.finder) {
            this.finder.addEventListener(EVENT_SET_SERVICE_POINT, (event: CustomEvent<ServicePointEventDetail>) =>
                this.onServicePointSelected(event.detail),
            );
        }
    }

    protected onServicePointSelected(detail: ServicePointEventDetail): void {
        this.popup.dispatchEvent(new CustomEvent(EVENT_CLOSE_POPUP));
        this.location.innerHTML = detail.address;
        this.toggleContainer();
        this.transferAttributes(detail);
        this.changePriceVisibility(detail.productOfferAvailability?.[0]?.productOfferReference);
        this.triggerShipmentTypeChange();
    }

    protected transferAttributes(detail: ServicePointEventDetail): void {
        document.querySelector<HTMLInputElement>(this.hiddenUuidSelector).value =
            detail.productOfferAvailability?.[0]?.servicePointUuid;
    }

    protected toggleContainer(): void {
        this.noLocationContainer?.classList.add(this.toggleClassName);
        this.locationContainer.classList.remove(this.toggleClassName);
    }

    protected triggerShipmentTypeChange(): void {
        const selectedShipmentType = document.querySelector<HTMLInputElement>(
            `.${this.shipmentType}__radio input:checked`,
        );

        if (selectedShipmentType) {
            selectedShipmentType.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    protected changeOffer(): void {
        document.addEventListener('change', (event: Event) => {
            const checker = (matcher: string) => (event.target as HTMLElement)?.matches(matcher);

            if (checker(this.offerReferenceSelector) || checker(`${this.merchantReferenceSelector}`)) {
                this.changePriceVisibility((event.target as HTMLInputElement).value);
            }
        });
    }

    protected changePriceVisibility(offer: string): void {
        this.querySelectorAll<HTMLInputElement>(
            `${this.merchantReferenceSelector}[type="radio"], ${this.offerReferenceSelector}[type="radio"]`,
        )?.forEach((input: HTMLInputElement) => {
            input.checked = input.value === offer;

            if (input.value !== offer) {
                input.removeAttribute('checked');
            }
        });

        this.querySelectorAll<HTMLInputElement>(`${this.offerReferenceSelector}[type="hidden"]`)?.forEach(
            (input: HTMLInputElement) => {
                input.value = offer;
            },
        );

        const elements = document.querySelectorAll(`[${this.productDataOfferAttribute}]`);
        let offerFound = false;

        elements?.forEach((element: HTMLElement) => {
            const value = element.getAttribute(this.productDataOfferAttribute);

            if (value === offer) {
                element.classList.remove(this.toggleClassName);
                offerFound = true;
                return;
            }

            element.classList.add(this.toggleClassName);
        });

        if (offerFound) {
            return;
        }

        const emptyElement = document.querySelector(`[${this.productDataOfferAttribute}=""]`);

        if (emptyElement) {
            emptyElement.classList.remove(this.toggleClassName);
        }
    }

    protected get finderClassName(): string {
        return this.getAttribute('finder-class-name');
    }

    protected get toggleClassName(): string {
        return this.getAttribute('toggle-class-name');
    }

    protected get hiddenUuidSelector(): string {
        return this.getAttribute('hidden-uuid-selector');
    }

    protected get offerReferenceSelector(): string {
        return this.getAttribute('offer-reference-input-selector');
    }

    protected get merchantReferenceSelector(): string {
        return this.getAttribute('merchant-reference-input-selector');
    }

    protected get productDataOfferAttribute(): string {
        return this.getAttribute('price-attribute');
    }

    protected get shipmentType(): string {
        return this.getAttribute('shipment-type');
    }
}
