import {Component, ElementRef, OnInit} from '@angular/core';
import {FormBuilder, Validators} from '@angular/forms';

import {NgbActiveModal, NgbTooltipConfig} from '@ng-bootstrap/ng-bootstrap';
import {TranslateService} from '@ngx-translate/core';

import {FormFieldsOptions} from '@app/models/form-fields-options.interface';
import {AppModalContentAbstractComponent} from '@app/components/app-modal-content.abstract';
import {AppSettings} from '@app/services/app-settings.service';
import {CommentsService} from '../services/comments.service';
import {Comment} from '../models/comment.model';

@Component({
    selector: 'app-modal-comment',
    templateUrl: './modal-comment.component.html',
    styles: []
})
export class ModalCommentComponent extends AppModalContentAbstractComponent<Comment> {

    model = new Comment(0, '');
    baseUrl = '';

    formFields: FormFieldsOptions[] = [
        {
            name: 'id',
            validators: []
        },
        {
            name: 'threadId',
            validators: [Validators.required]
        },
        {
            name: 'vote',
            validators: [Validators.required]
        },
        {
            name: 'status',
            validators: [Validators.required]
        },
        {
            name: 'comment',
            validators: [Validators.required]
        },
        {
            name: 'reply',
            validators: []
        }
    ];

    constructor(
        public fb: FormBuilder,
        public activeModal: NgbActiveModal,
        public translateService: TranslateService,
        public dataService: CommentsService,
        public elRef: ElementRef,
        private appSettings: AppSettings
    ) {
        super(fb, activeModal, translateService, dataService, elRef);
        this.baseUrl = `${this.appSettings.settings.webApiUrl}/`;
    }
}
