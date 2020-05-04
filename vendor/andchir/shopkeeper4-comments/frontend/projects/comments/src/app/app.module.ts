import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';

import {TranslateLoader, TranslateModule} from '@ngx-translate/core';
import {TranslateCustomLoader} from '@app/services/translateLoader';

import {
    AlertModalContentComponent,
    ConfirmModalContentComponent,
    ModalConfirmTextComponent
} from '@app/components/modal-confirm-text.component';
import {SharedModule} from '@app/shared.module';
import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {DefaultComponent} from './default/default.component';
import {HomeComponent} from './home/home.component';
import {ModalCommentComponent} from './home/modal-comment.component';

@NgModule({
    declarations: [
        AppComponent,
        DefaultComponent,
        HomeComponent,

        AlertModalContentComponent,
        ConfirmModalContentComponent,
        ModalConfirmTextComponent,
        ModalCommentComponent
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        SharedModule,
        TranslateModule.forRoot({
            loader: {
                provide: TranslateLoader,
                useClass: TranslateCustomLoader
            }
        })
    ],
    entryComponents: [
        AlertModalContentComponent,
        ConfirmModalContentComponent,
        ModalConfirmTextComponent,
        ModalCommentComponent
    ],
    providers: [],
    bootstrap: [AppComponent]
})
export class AppModule {
}
