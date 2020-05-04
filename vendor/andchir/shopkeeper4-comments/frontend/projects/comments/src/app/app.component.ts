import {Component} from '@angular/core';

import {NgbTooltipConfig} from "@ng-bootstrap/ng-bootstrap";
import {TranslateService} from "@ngx-translate/core";

import {AppSettings} from "@app/services/app-settings.service";

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})
export class AppComponent {

    constructor(
        private tooltipConfig: NgbTooltipConfig,
        private translate: TranslateService,
        private appSettings: AppSettings
    ) {
        this.translate.addLangs(['en', 'ru']);
        this.translate.setDefaultLang('en');
        this.translate.use(this.appSettings.settings.locale);

        tooltipConfig.placement = 'bottom';
        tooltipConfig.container = 'body';
        tooltipConfig.triggers = 'hover click';
    }

}
