import {Injectable} from '@angular/core';
import {HttpClient, HttpParams, HttpRequest, HttpEventType} from '@angular/common/http';

import {Observable, of} from 'rxjs';
import {catchError, map, tap} from 'rxjs/operators';

import {ImportTestData, FieldOption, ImportConfiguration} from '../models/import-configuration.model';
import {DataService} from '@app/services/data-service.abstract';
import {SheetProperties} from '../models/sheet-properties.interface';
import {Properties} from '@app/models/properties.iterface';

@Injectable()
export class ImportService extends DataService<ImportConfiguration> {

    constructor(http: HttpClient) {
        super(http);
        this.setRequestUrl('import');
    }

    getSheetProperties(item: ImportConfiguration): Observable<SheetProperties> {
        const url = this.getRequestUrl() + `/${item.id}/properties`;
        return this.http.post<SheetProperties>(url, item, {headers: this.headers}).pipe(
            catchError(this.handleError<SheetProperties>())
        );
    }

    getFieldsOptions(id: number, options: Properties): Observable<FieldOption[]> {
        const url = this.getRequestUrl() + `/${id}/fields_options`;
        let params = this.createHttpParams(options);
        return this.http.get<FieldOption[]>(url, {params: params, headers: this.headers}).pipe(
            catchError(this.handleError<FieldOption[]>())
        );
    }

    splitFileAction(id: number): Observable<any> {
        const url = this.getRequestUrl() + `/${id}/split_file`;
        return this.http.post<any>(url, {}, {headers: this.headers}).pipe(
            catchError(this.handleError<any>())
        );
    }

    testData(item: ImportConfiguration): Observable<ImportTestData> {
        const url = this.getRequestUrl() + `/${item.id}/do_import_test`;

        return this.http.post<ImportTestData>(url, item, {headers: this.headers}).pipe(
            catchError(this.handleError<any>())
        );
    }

    importData(item: ImportConfiguration): Observable<any> {
        const url = this.getRequestUrl() + `/${item.id}/do_import`;
        return this.http.post<any>(url, item, {headers: this.headers}).pipe(
            catchError(this.handleError<any>())
        );
    }

    importDataProgress(item: ImportConfiguration): Observable<any> {
        const url = this.getRequestUrl() + `/${item.id}/do_import`;
        return this.http.request(new HttpRequest('POST', url, item, {
            reportProgress: true,
            responseType: 'text',
            headers: this.headers
        }));
    }

    getPercent(item: ImportConfiguration): Observable<any> {
        const url = this.getRequestUrl() + `/${item.id}/percent`;
        return this.http.get<any>(url, {headers: this.headers}).pipe(
            catchError(this.handleError<any>())
        );
    }

}
