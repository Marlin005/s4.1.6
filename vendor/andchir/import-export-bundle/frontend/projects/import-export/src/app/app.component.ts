import {Component} from '@angular/core';

import {TranslateService} from '@ngx-translate/core';
import {NgbTooltipConfig} from '@ng-bootstrap/ng-bootstrap';

import {MenuItem} from '@app/models/menu-item.interface';
import {AppSettings} from '@app/services/app-settings.service';

declare const adminMenu: MenuItem[];

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css'],
    providers: [NgbTooltipConfig],
})
export class AppComponent {

    moduleRoute = '/module/import-export';
    baseUrl: string;
    routeData: any[];
    menuItems: MenuItem[] = [...adminMenu];
    appVersion: string;

    constructor(
        tooltipConfig: NgbTooltipConfig,
        private translate: TranslateService,
        private appSettings: AppSettings
    ) {
        this.baseUrl = this.appSettings.settings.webApiUrl + '/';

        this.translate.addLangs(['en', 'ru']);
        this.translate.setDefaultLang('en');
        this.translate.use(this.appSettings.settings.locale);
        this.appVersion = this.appSettings.settings.version;

        tooltipConfig.placement = 'bottom';
        tooltipConfig.container = 'body';
        tooltipConfig.triggers = 'hover click';

    }

}
