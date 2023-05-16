import { TestBed, inject } from '@angular/core/testing';
import {
  HttpClient,
  HttpErrorResponse,
  HTTP_INTERCEPTORS
} from '@angular/common/http';
import {
  HttpClientTestingModule,
  HttpTestingController
} from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';

import { AuthService } from 'src/app/shared/auth/auth.service';
import { ApiInterceptor } from 'src/app/app.api-http';

describe('ApiInterceptor', () => {
  const mockAuthService = { };

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [
        HttpClientTestingModule,
        RouterTestingModule.withRoutes([])
      ],
      providers: [
        {
          provide: AuthService,
          useValue: mockAuthService
        },
        {
          provide: HTTP_INTERCEPTORS,
          useClass: ApiInterceptor,
          multi: true
        }
      ]
    });
  });

  afterEach(inject([HttpTestingController], (httpMock: HttpTestingController) => {
    httpMock.verify();
  }));

  it('adds Content-Type header',
    inject([HttpClient, HttpTestingController],
      (http: HttpClient, httpMock: HttpTestingController) => {
        http.get('').subscribe(response => {
          expect(response).toBeTruthy();
        });

        const req = httpMock.expectOne(r =>
          r.headers.has('Content-Type') &&
          r.headers.get('Content-Type') === 'application/json'
        );
        expect(req.request.method).toEqual('GET');

        req.flush({});
      }
    )
  );

  it('adds Authorization header when JWT is present',
    inject([HttpClient, HttpTestingController],
      (http: HttpClient, httpMock: HttpTestingController) => {
        sessionStorage.setItem('taskboard.jwt', 'fake');

        http.post('', {}).subscribe(response => {
          expect(response).toBeTruthy();
        });

        const req = httpMock.expectOne(r =>
          r.headers.has('Authorization') &&
          r.headers.get('Authorization') === 'fake'
        );
        expect(req.request.method).toEqual('POST');

        req.flush({ data: ['newToken'] });
        expect(sessionStorage.getItem('taskboard.jwt')).toEqual('newToken');

        sessionStorage.removeItem('taskboard.jwt');

        http.post('', new FormData()).subscribe(response => {
          expect(response).toBeTruthy();
        });

        const req2 = httpMock.expectOne(r => !r.headers.has('Authorization'));
        expect(req2.request.method).toEqual('POST');
      }
    )
  );

  it('handles errors and clears the JWT',
    inject([HttpClient, HttpTestingController],
      (http: HttpClient, httpMock: HttpTestingController) => {
        sessionStorage.setItem('taskboard.jwt', null);

        http.get('').subscribe(response => {
          expect(response).toBeTruthy();
        }, err => {
          expect(err).toBeTruthy();
        });

        const req = httpMock.expectOne(r =>
          r.headers.has('Content-Type') &&
          r.headers.get('Content-Type') === 'application/json'
        );
        expect(req.request.method).toEqual('GET');

        const error = new HttpErrorResponse({ status: 401 });
        req.flush(error, { status: 401, statusText: '' });
        expect(sessionStorage.getItem('Authorization')).toEqual(null);
      }
    )
  );

});

