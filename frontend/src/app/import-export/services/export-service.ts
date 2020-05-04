import {Injectable} from '@angular/core';
import {HttpClient, HttpParams, HttpRequest, HttpEventType} from '@angular/common/http';

import {Observable, of} from 'rxjs';
import {catchError, map, tap} from 'rxjs/operators';

import {ExportConfiguration} from '../models/export-configuration.model';
import {DataService} from '@app/services/data-service.abstract';;

@Injectable()
export class ExportService extends DataService<ExportConfiguration> {

    constructor(http: HttpClient) {
        super(http);
        this.setRequestUrl('export');
    }

    exportDataProgress(item: ExportConfiguration): Observable<any> {
        const url = this.getRequestUrl() + `/${item.id}/do_export`;
        return this.http.request(new HttpRequest('POST', url, item, {
            reportProgress: true,
            responseType: 'text',
            headers: this.headers
        }));
    }
}
